<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Services\DeliveryNoteCompletionService;
use App\Services\DeliveryNoteService;
use App\Services\ValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;
    use HandlesTransactionDocumentationEdit;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'delivery';
    }

    public function __construct(
        private DeliveryNoteService $deliveryNoteService,
        private DeliveryNoteCompletionService $completionService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_delivery');

        $companyId = auth()->user()->company_id;
        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $query = $this->buildIndexQuery($request, $companyId, $activeFy);

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true)
            ? (int) $request->per_page
            : 20;

        $activeQuery = (clone $query)->where('status', '!=', DeliveryNote::STATUS_CANCELLED);

        $totalCount = (clone $query)->count();
        $activeCount = (clone $activeQuery)->count();
        $cancelledCount = (clone $query)->where('status', DeliveryNote::STATUS_CANCELLED)->count();

        $deliveryNotes = $query
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->active()
            ->orderBy('first_name')
            ->get(['id', 'employee_code', 'first_name', 'middle_name', 'last_name']);

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        return view('company.delivery-notes.index', compact(
            'deliveryNotes',
            'customers',
            'employees',
            'financialYears',
            'activeFy',
            'totalCount',
            'activeCount',
            'cancelledCount',
            'perPage'
        ));
    }

    public function create(Request $request)
    {
        $this->authorizeCompanyPermission('create_delivery');

        $companyId = auth()->user()->company_id;

        try {
            $activeFy = $this->assertActiveFinancialYear($companyId);
        } catch (\Exception $e) {
            return redirect()
                ->route('company.delivery-notes.index')
                ->with('error', $e->getMessage());
        }

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $salesInvoices = collect();
        $selectedInvoice = null;
        $lineItems = [];

        if ($request->filled('customer_id')) {
            $salesInvoices = SalesInvoice::where('company_id', $companyId)
                ->where('customer_id', $request->customer_id)
                ->where('status', 1)
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->get(['id', 'invoice_no', 'sale_date', 'customer_id']);
        }

        if ($request->filled('sales_invoice_id')) {
            $selectedInvoice = SalesInvoice::where('company_id', $companyId)
                ->where('status', 1)
                ->when($request->filled('customer_id'), function ($query) use ($request) {
                    $query->where('customer_id', $request->customer_id);
                })
                ->findOrFail($request->sales_invoice_id);

            $lineItems = $this->deliveryNoteService->buildCreateLineItems($companyId, $selectedInvoice);
        }

        return view('company.delivery-notes.create', compact(
            'employees',
            'customers',
            'salesInvoices',
            'selectedInvoice',
            'lineItems',
            'activeFy'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_delivery');

        $companyId = auth()->user()->company_id;

        $request->validate([
            'employee_id' => [
                'required',
                ValidationService::existsForCompany('employee_accounts', $companyId),
            ],
            'customer_id' => [
                'required',
                ValidationService::existsForCompany('customers', $companyId),
            ],
            'sales_invoice_id' => [
                'required',
                ValidationService::existsForCompany('sales_invoices', $companyId),
            ],
            'delivery_date' => ValidationService::requiredDate(),
            'remarks' => ValidationService::text(),
            'items' => 'required|array|min:1',
            'items.*.sales_item_id' => 'required|integer',
            'items.*.planned_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $companyId) {
                $activeFy = $this->assertActiveFinancialYear($companyId);
                $this->assertDateWithinFinancialYear(
                    $request->delivery_date,
                    $activeFy,
                    'Delivery date must fall within the active financial year.'
                );

                $employee = EmployeeAccount::where('company_id', $companyId)
                    ->active()
                    ->findOrFail($request->employee_id);

                $invoice = SalesInvoice::with('items')
                    ->where('company_id', $companyId)
                    ->where('customer_id', $request->customer_id)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->findOrFail($request->sales_invoice_id);

                $deliveryNo = DeliveryNoteService::generateDeliveryNo($companyId);
                $hasPlannedLine = false;

                $deliveryNote = DeliveryNote::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'delivery_no' => $deliveryNo,
                    'customer_id' => $request->customer_id,
                    'employee_id' => $employee->id,
                    'sales_invoice_id' => $invoice->id,
                    'delivery_date' => $request->delivery_date,
                    'status' => DeliveryNote::STATUS_READY,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id(),
                ]);

                $salesItems = $invoice->items->keyBy('id');

                foreach ($request->items as $row) {
                    $salesItemId = (int) ($row['sales_item_id'] ?? 0);
                    $plannedQty = round((float) ($row['planned_qty'] ?? 0), 2);

                    if ($plannedQty <= 0 || !$salesItems->has($salesItemId)) {
                        continue;
                    }

                    /** @var SalesItem $salesItem */
                    $salesItem = $salesItems->get($salesItemId);
                    $remainingQty = $this->deliveryNoteService->remainingQtyForSalesItem($companyId, $salesItem);

                    if ($plannedQty > $remainingQty) {
                        throw new \Exception(
                            'Planned quantity exceeds remaining quantity for '
                            . $this->deliveryNoteService->resolveItemName($salesItem)
                            . '. Maximum allowed: '
                            . number_format($remainingQty, 2)
                            . '.'
                        );
                    }

                    DeliveryNoteItem::create([
                        'company_id' => $companyId,
                        'delivery_note_id' => $deliveryNote->id,
                        'sales_item_id' => $salesItem->id,
                        'item_type' => $salesItem->item_type,
                        'product_id' => $salesItem->product_id,
                        'service_id' => $salesItem->service_id,
                        'invoice_qty' => $salesItem->quantity,
                        'planned_qty' => $plannedQty,
                        'delivered_qty' => 0,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]);

                    $hasPlannedLine = true;
                }

                if (!$hasPlannedLine) {
                    throw new \Exception('At least one delivery line with planned quantity greater than zero is required.');
                }

                $this->deliveryNoteService->recordStatusHistory(
                    $deliveryNote,
                    DeliveryNote::STATUS_DRAFT,
                    DeliveryNote::STATUS_READY,
                    'Delivery note created and assigned to employee.'
                );
            });

            return redirect()
                ->route('company.delivery-notes.index')
                ->with('success', 'Delivery note created successfully. Status: Ready.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_delivery');

        $companyId = auth()->user()->company_id;

        $deliveryNote = DeliveryNote::with([
            'customer',
            'employee',
            'salesInvoice',
            'financialYear',
            'creator',
            'updater',
            'canceller',
            'completer',
            'signature',
            'attachments',
            'statusHistories.changer',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $lineItems = $this->deliveryNoteService->buildShowLineItems($companyId, $deliveryNote);

        return view('company.delivery-notes.show', compact('deliveryNote', 'lineItems'));
    }

    public function process($id)
    {
        $this->authorizeCompanyPermission('process_delivery');

        $companyId = auth()->user()->company_id;

        $deliveryNote = DeliveryNote::with([
            'customer',
            'employee',
            'salesInvoice',
            'financialYear',
            'items.product',
            'items.service',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        if (!$deliveryNote->isProcessable()) {
            return redirect()
                ->route('company.delivery-notes.show', $deliveryNote->id)
                ->with('error', 'This delivery note is not available for completion.');
        }

        $lineItems = $deliveryNote->items->map(function (DeliveryNoteItem $item) {
            return [
                'item' => $item,
                'item_name' => $this->deliveryNoteService->resolveItemName($item),
                'planned_qty' => round((float) $item->planned_qty, 2),
            ];
        });

        return view('company.delivery-notes.process', compact('deliveryNote', 'lineItems'));
    }

    public function complete(Request $request, $id)
    {
        $this->authorizeCompanyPermission('process_delivery');

        $request->validate([
            'receiver_name' => ValidationService::requiredString(150),
            'receiver_mobile' => ValidationService::requiredString(30),
            'signature_data' => 'required|string',
            'photo_1' => ValidationService::requiredImage(),
            'photo_2' => ValidationService::image(),
            'items' => 'required|array|min:1',
            'items.*.delivery_note_item_id' => 'required|integer',
            'items.*.selected' => 'nullable|boolean',
            'items.*.delivered_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            $deliveryNote = DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $deliveryNote = DeliveryNote::with('items')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                return $this->completionService->complete(
                    $deliveryNote,
                    $request,
                    $request->items
                );
            });

            return redirect()
                ->route('company.delivery-notes.show', $deliveryNote->id)
                ->with('success', 'Delivery completed successfully. PDF generated and email sent if customer email exists.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $this->authorizeCompanyPermission('print_delivery');

        $companyId = auth()->user()->company_id;
        $company = auth()->user()->company;

        $deliveryNote = DeliveryNote::with([
            'customer',
            'employee',
            'salesInvoice',
            'financialYear',
            'signature',
            'attachments',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        if (!$deliveryNote->isCompleted()) {
            return redirect()
                ->route('company.delivery-notes.show', $deliveryNote->id)
                ->with('error', 'PDF is available only after delivery is completed.');
        }

        $lineItems = $this->deliveryNoteService->buildShowLineItems($companyId, $deliveryNote);

        if ($deliveryNote->pdf_path && is_file(public_path('companies/' . $companyId . '/' . $deliveryNote->pdf_path))) {
            return response()->file(public_path('companies/' . $companyId . '/' . $deliveryNote->pdf_path));
        }

        $pdf = Pdf::loadView('company.delivery-notes.pdf', compact(
            'deliveryNote',
            'lineItems',
            'company'
        ))->setPaper('a4');

        return $pdf->stream($deliveryNote->delivery_no . '.pdf');
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_delivery');

        $request->validate([
            'cancel_reason' => ValidationService::requiredString(500),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $deliveryNote = DeliveryNote::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!$deliveryNote->isCancellable()) {
                    throw new \Exception('This delivery note cannot be cancelled.');
                }

                if ($deliveryNote->isCancelled()) {
                    throw new \Exception('Delivery note already cancelled.');
                }

                $previousStatus = $deliveryNote->status;
                $cancelReason = trim($request->cancel_reason);

                $deliveryNote->update([
                    'status' => DeliveryNote::STATUS_CANCELLED,
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancel_reason' => $cancelReason,
                    'updated_by' => auth()->id(),
                    'remarks' => trim(($deliveryNote->remarks ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);

                $this->deliveryNoteService->recordStatusHistory(
                    $deliveryNote,
                    $previousStatus,
                    DeliveryNote::STATUS_CANCELLED,
                    $cancelReason
                );
            });

            return back()->with('success', 'Delivery note cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function buildIndexQuery(Request $request, int $companyId, ?FinancialYear $activeFy)
    {
        $query = DeliveryNote::with(['customer', 'employee', 'salesInvoice', 'financialYear'])
            ->where('company_id', $companyId);

        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        } elseif (
            !$request->has('financial_year_id')
            && $activeFy
        ) {
            $query->where('financial_year_id', $activeFy->id);
        }

        if (!$request->has('status')) {
            $query->where('status', '!=', DeliveryNote::STATUS_CANCELLED);
        } elseif ($request->status === 'all') {
            // Show all statuses including cancelled.
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', DeliveryNote::STATUS_CANCELLED);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('delivery_no', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('salesInvoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('invoice_no', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('delivery_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('delivery_date', '<=', $request->end_date);
        }

        return $query;
    }
}
