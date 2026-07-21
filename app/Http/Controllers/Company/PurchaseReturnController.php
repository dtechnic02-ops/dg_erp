<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Services\InvoiceNumberService;
use App\Services\PurchaseReturnSyncService;
use App\Services\StockService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = PurchaseReturn::with([
            'supplier',
            'invoice',
        ])
            ->where('company_id', $companyId);

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $startDate = null;
        $endDate = null;

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate = $activeFy->end_date;
            }
        } else {
            if ($request->financial_year_id) {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        if (!empty($startDate)) {
            $query->whereDate('return_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('return_date', '<=', $endDate);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if (!$request->has('status')) {
            $query->where('status', 1);
        } elseif ($request->status === '1') {
            $query->where('status', 1);
        } elseif ($request->status === '0') {
            $query->where('status', 0);
        }

        if ($request->filled('refund_status')) {
            if ($request->refund_status === 'unpaid') {
                $query->where('adjust_amount', '<=', 0);
            } elseif ($request->refund_status === 'paid') {
                $query->where('adjust_amount', '>', 0)
                    ->where('refund_amount', '<=', 0);
            } elseif ($request->refund_status === 'partial') {
                $query->where('adjust_amount', '>', 0)
                    ->where('refund_amount', '>', 0);
            }
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 100, 200], true)
            ? (int) $request->per_page
            : 20;

        $totalsQuery = (clone $query)->where('status', 1);

        $totalSubtotal = (clone $totalsQuery)->sum('subtotal');
        $totalVat = (clone $totalsQuery)->sum('total_vat');
        $grandTotal = (clone $totalsQuery)->sum('grand_total');

        $returns = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::where('company_id', $companyId)->get();

        return view(
            'company.purchase-return.index',
            compact(
                'returns',
                'suppliers',
                'grandTotal',
                'totalSubtotal',
                'totalVat',
                'financialYears',
                'activeFy',
                'startDate',
                'endDate',
                'perPage'
            )
        );
    }

    public function printList(Request $request)
    {
        $request->merge(['print' => 1]);

        return $this->index($request);
    }

    public function create($id)
    {
        $companyId = auth()->user()->company_id;

        $invoice = PurchaseInvoice::with([
            'supplier',
            'items.product',
            'items.service',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        if ($invoice->status != 1) {
            return redirect()
                ->route('company.purchases.show', $invoice->id)
                ->with('error', 'Cannot create return for a cancelled purchase invoice.');
        }

        if ((float) $invoice->paid_amount > 0) {
            return redirect()
                ->route('company.purchases.show', $invoice->id)
                ->with(
                    'error',
                    'This invoice has received payment. Cancel or reverse the payment before creating a Purchase Return.'
                );
        }

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            return back()->with(
                'error',
                'Please activate financial year first.'
            );
        }

        if ((int) $invoice->financial_year_id !== (int) $activeFy->id) {
            return redirect()
                ->route('company.purchases.show', $invoice->id)
                ->with('error', 'Purchase Invoice belongs to another Financial Year.');
        }

        $availableQuantities = $this->calculateAvailableQuantities($invoice, $companyId);

        if (!$this->invoiceHasReturnableQuantity($availableQuantities)) {
            return redirect()
                ->back()
                ->with('error', 'This invoice has already been fully returned.');
        }

        $returnNo = InvoiceNumberService::generate(
            'PR',
            $companyId,
            $activeFy->id,
            PurchaseReturn::class,
            'return_no'
        );

        return view(
            'company.purchase-return.create',
            compact(
                'invoice',
                'returnNo',
                'availableQuantities'
            )
        );
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id,company_id,' . $companyId,
            'supplier_id'         => 'required|exists:suppliers,id,company_id,' . $companyId,
            'return_date'         => 'required|date',
            'purchase_item_id'    => 'required|array|min:1',
            'purchase_item_id.*'  => 'required|integer',
            'quantity'            => 'required|array',
            'quantity.*'          => 'nullable|numeric|min:0',
            'note'                => 'nullable|string|max:1000',
            'damage_photo'        => 'nullable|image|max:5120',
        ]);

        $safeMessages = [
            'Please activate financial year first.',
            'No active financial year found for selected return date.',
            'Purchase Invoice belongs to another Financial Year.',
            'Return qty exceeds available qty.',
            'Please enter return qty.',
            'Cannot return from a cancelled purchase invoice.',
            'This invoice has received payment. Cancel or reverse the payment before creating a Purchase Return.',
            'Invalid purchase item for this invoice.',
            'Supplier does not match the selected purchase invoice.',
            'Invalid quantity',
            'Insufficient stock.',
            'Financial Year is required for stock transaction.',
        ];

        try {
            $return = DB::transaction(function () use ($request, $companyId) {
                $activeFy = FinancialYear::where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->first();

                if (!$activeFy) {
                    throw new \Exception('Please activate financial year first.');
                }

                $returnDate = \Carbon\Carbon::parse($request->return_date);
                $startDate = \Carbon\Carbon::parse($activeFy->start_date);
                $endDate = \Carbon\Carbon::parse($activeFy->end_date);

                if ($returnDate->lt($startDate) || $returnDate->gt($endDate)) {
                    throw new \Exception(
                        'No active financial year found for selected return date.'
                    );
                }

                $invoice = PurchaseInvoice::with('items')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->purchase_invoice_id);

                if ($invoice->status != 1) {
                    throw new \Exception(
                        'Cannot return from a cancelled purchase invoice.'
                    );
                }

                if ((float) $invoice->paid_amount > 0) {
                    throw new \Exception(
                        'This invoice has received payment. Cancel or reverse the payment before creating a Purchase Return.'
                    );
                }

                if ((int) $invoice->supplier_id !== (int) $request->supplier_id) {
                    throw new \Exception(
                        'Supplier does not match the selected purchase invoice.'
                    );
                }

                if ($invoice->financial_year_id != $activeFy->id) {
                    throw new \Exception(
                        'Purchase Invoice belongs to another Financial Year.'
                    );
                }

                $returnNo = InvoiceNumberService::generate(
                    'PR',
                    $companyId,
                    $activeFy->id,
                    PurchaseReturn::class,
                    'return_no'
                );

                $photo = null;

                if ($request->hasFile('damage_photo')) {
                    $photo = $request
                        ->file('damage_photo')
                        ->store(
                            "companies/{$companyId}/returns",
                            'public'
                        );
                }

                $return = PurchaseReturn::create([
                    'company_id'          => $companyId,
                    'financial_year_id'   => $activeFy->id,
                    'purchase_invoice_id' => $request->purchase_invoice_id,
                    'supplier_id'         => $request->supplier_id,
                    'return_no'           => $returnNo,
                    'return_date'         => $request->return_date,
                    'subtotal'            => 0,
                    'total_vat'           => 0,
                    'grand_total'         => 0,
                    'note'                => $request->note,
                    'damage_photo'        => $photo,
                    'created_by'          => auth()->id(),
                    'status'              => 1,
                ]);

                $totalSubtotal = 0;
                $totalVat = 0;
                $grandTotal = 0;
                $hasReturn = false;

                foreach ($request->purchase_item_id as $key => $purchaseItemId) {
                    $returnQty = (float) ($request->quantity[$key] ?? 0);

                    if ($returnQty <= 0) {
                        continue;
                    }

                    $hasReturn = true;

                    $purchaseItem = PurchaseItem::where('company_id', $companyId)
                        ->lockForUpdate()
                        ->findOrFail($purchaseItemId);

                    if ((int) $purchaseItem->purchase_invoice_id !== (int) $invoice->id) {
                        throw new \Exception(
                            'Invalid purchase item for this invoice.'
                        );
                    }

                    $availableQty = max(
                        0,
                        round(
                            (float) $purchaseItem->quantity - (float) $purchaseItem->returned_qty,
                            2
                        )
                    );

                    if ($returnQty > $availableQty) {
                        throw new \Exception(
                            'Return qty exceeds available qty.'
                        );
                    }

                    $subtotal = $returnQty * $purchaseItem->unit_price;

                    $vatAmount = round(
                        ($subtotal * $purchaseItem->vat_rate) / 100,
                        2
                    );

                    $total = $subtotal + $vatAmount;

                    $totalSubtotal += $subtotal;
                    $totalVat += $vatAmount;
                    $grandTotal += $total;

                    $returnItemData = [
                        'company_id'          => $companyId,
                        'financial_year_id'   => $activeFy->id,
                        'purchase_return_id'  => $return->id,
                        'purchase_invoice_id' => $invoice->id,
                        'purchase_item_id'    => $purchaseItem->id,
                        'quantity'            => $returnQty,
                        'unit_price'          => $purchaseItem->unit_price,
                        'vat_rate'            => $purchaseItem->vat_rate,
                        'vat_amount'          => $vatAmount,
                        'total_price'         => $total,
                        'created_by'          => auth()->id(),
                        'status'              => 1,
                    ];

                    if ($purchaseItem->item_type === 'product') {
                        if (!$purchaseItem->product_id) {
                            throw new \Exception(
                                'Invalid purchase item for this invoice.'
                            );
                        }

                        $product = Product::where('company_id', $companyId)
                            ->lockForUpdate()
                            ->findOrFail($purchaseItem->product_id);

                        $returnItemData['product_id'] = $product->id;
                        $returnItemData['service_id'] = null;

                        PurchaseReturnItem::create($returnItemData);

                        $purchaseItem->update([
                            'returned_qty' => round(
                                (float) $purchaseItem->returned_qty + $returnQty,
                                2
                            ),
                        ]);

                        StockService::decrease(
                            $product,
                            $returnQty,
                            'purchase_return',
                            $return->return_no,
                            $activeFy->id,
                            $return->return_date,
                            $purchaseItem->unit_price,
                            'Purchase Return'
                        );

                        continue;
                    }

                    if ($purchaseItem->item_type !== 'service' || !$purchaseItem->service_id) {
                        throw new \Exception(
                            'Invalid purchase item for this invoice.'
                        );
                    }

                    $returnItemData['product_id'] = null;
                    $returnItemData['service_id'] = $purchaseItem->service_id;

                    PurchaseReturnItem::create($returnItemData);

                    $purchaseItem->update([
                        'returned_qty' => round(
                            (float) $purchaseItem->returned_qty + $returnQty,
                            2
                        ),
                    ]);
                }

                if (!$hasReturn) {
                    throw new \Exception(
                        'Please enter return qty.'
                    );
                }

                $return->update([
                    'subtotal'    => $totalSubtotal,
                    'total_vat'   => $totalVat,
                    'grand_total' => $grandTotal,
                ]);

                PurchaseReturnSyncService::sync($return, true);

                return $return;
            });

            return redirect()
                ->route('company.purchase-return.show', $return->id)
                ->with('success', 'Purchase return saved successfully.');
        } catch (\Throwable $e) {
            $this->logReturnException('Purchase return store failed.', $e, [
                'purchase_invoice_id' => $request->purchase_invoice_id,
                'supplier_id'         => $request->supplier_id,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to save purchase return. Please try again.'
            );

            return back()
                ->withInput()
                ->with('error', $error);
        }
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id;

        $return = PurchaseReturn::with([
            'supplier',
            'invoice',
            'items.product',
            'items.purchaseItem.service',
            'refunds',
            'financialYear',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view(
            'company.purchase-return.show',
            compact('return')
        );
    }

    public function print($id)
    {
        $companyId = auth()->user()->company_id;

        $return = PurchaseReturn::with([
            'supplier',
            'invoice',
            'items.product',
            'items.purchaseItem.service',
            'financialYear',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view(
            'company.purchase-return.print',
            compact('return')
        );
    }

    public function cancel(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'cancel_date'   => ValidationService::requiredDate(),
            'cancel_reason' => ValidationService::requiredString(500),
        ]);

        $safeMessages = [
            'Purchase return already cancelled.',
            'Cannot cancel a purchase return with refund settlements.',
            'Cancel date must belong to the active financial year.',
        ];

        try {
            DB::transaction(function () use ($request, $id, $companyId) {
                $activeFy = FinancialYear::where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->firstOrFail();

                $cancelDate = \Carbon\Carbon::parse($request->cancel_date);
                $startDate  = \Carbon\Carbon::parse($activeFy->start_date);
                $endDate    = \Carbon\Carbon::parse($activeFy->end_date);

                if ($cancelDate->lt($startDate) || $cancelDate->gt($endDate)) {
                    throw new \Exception(
                        'Cancel date must belong to the active financial year.'
                    );
                }

                $cancelBusinessDate = $cancelDate->toDateString();
                $cancelReason = trim($request->cancel_reason);

                $return = PurchaseReturn::with('items')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ((int) $return->status !== 1) {
                    throw new \Exception('Purchase return already cancelled.');
                }

                $refundedAmount = PurchaseReturnSyncService::calculateRefundedAmount(
                    $return,
                    true
                );

                if ($refundedAmount > 0) {
                    throw new \Exception('Cannot cancel a purchase return with refund settlements.');
                }

                foreach ($return->items as $item) {
                    if ((int) $item->status !== 1) {
                        continue;
                    }

                    if ($item->product_id) {
                        $product = Product::where('company_id', $companyId)
                            ->lockForUpdate()
                            ->findOrFail($item->product_id);

                        StockService::increase(
                            $product,
                            $item->quantity,
                            'purchase_return_cancel',
                            $return->return_no,
                            $activeFy->id,
                            $cancelBusinessDate,
                            $item->unit_price,
                            'Purchase Return Cancel: ' . $cancelReason
                        );
                    }

                    if ($item->purchase_item_id) {
                        $purchaseItem = PurchaseItem::where('company_id', $companyId)
                            ->lockForUpdate()
                            ->find($item->purchase_item_id);

                        if ($purchaseItem) {
                            $purchaseItem->update([
                                'returned_qty' => max(
                                    0,
                                    round(
                                        (float) $purchaseItem->returned_qty - (float) $item->quantity,
                                        2
                                    )
                                ),
                            ]);
                        }
                    }

                    $item->update(['status' => 0]);
                }

                $return->update([
                    'status' => 0,
                    'note'   => trim(($return->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);

                PurchaseReturnSyncService::sync($return, true);
            });

            return redirect()
                ->route('company.purchase-return.index')
                ->with('success', 'Purchase return cancelled successfully.');
        } catch (\Throwable $e) {
            $this->logReturnException('Purchase return cancel failed.', $e, [
                'purchase_return_id' => $id,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to cancel purchase return. Please try again.'
            );

            return back()->with('error', $error);
        }
    }

    public function attachReturnableQuantityToInvoices($invoices, int $companyId): void
    {
        $invoiceCollection = $invoices->getCollection();

        $invoiceCollection->loadMissing('items');

        foreach ($invoiceCollection as $invoice) {
            $availableQuantities = $this->calculateAvailableQuantities(
                $invoice,
                $companyId
            );

            $invoice->setAttribute(
                'has_returnable_quantity',
                $this->invoiceHasReturnableQuantity($availableQuantities)
            );
        }
    }

    public static function invoiceHasReturnableQuantityForInvoice(
        PurchaseInvoice $invoice,
        int $companyId
    ): bool {
        $invoice->loadMissing('items');

        $controller = app(self::class);

        return $controller->invoiceHasReturnableQuantity(
            $controller->calculateAvailableQuantities($invoice, $companyId)
        );
    }

    protected function calculateAvailableQuantities(
        PurchaseInvoice $invoice,
        int $companyId
    ): array {
        $availableQuantities = [];

        foreach ($invoice->items as $item) {
            $availableQuantities[$item->id] = max(
                0,
                (float) $item->quantity - (float) $item->returned_qty
            );
        }

        return $availableQuantities;
    }

    protected function invoiceHasReturnableQuantity(array $availableQuantities): bool
    {
        foreach ($availableQuantities as $quantity) {
            if ((float) $quantity > 0) {
                return true;
            }
        }

        return false;
    }

    protected function resolveSafeExceptionMessage(
        \Throwable $e,
        array $safeMessages,
        string $fallback
    ): string {
        $message = $e->getMessage();

        if (in_array($message, $safeMessages, true)) {
            return $message;
        }

        return $fallback;
    }

    protected function logReturnException(
        string $context,
        \Throwable $e,
        array $extra = []
    ): void {
        Log::error($context, array_merge([
            'company_id' => auth()->user()->company_id ?? null,
            'user_id'    => auth()->id(),
            'exception'  => get_class($e),
            'message'    => $e->getMessage(),
        ], $extra));
    }
}
