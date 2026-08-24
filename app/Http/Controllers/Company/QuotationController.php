<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\Service;
use App\Models\Vat;
use App\Services\InvoiceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with(['customer', 'salesInvoice'])
            ->where('company_id', auth()->user()->company_id)
            ->latest('quotation_date')->latest('id')->paginate(25);

        return view('company.quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('company.quotations.create', $this->formData());
    }

    public function store(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $validated = $this->validateQuotation($request, $companyId);

        $quotation = DB::transaction(function () use ($request, $validated, $companyId) {
            $activeFy = $this->activeFinancialYear($companyId, true);
            $amounts = app(SalesController::class)->calculateStoreAmounts($request, $companyId);
            $quotationNo = InvoiceNumberService::generate('QT', $companyId, $activeFy->id, Quotation::class, 'quotation_no');

            $quotation = Quotation::create([
                'company_id' => $companyId,
                'financial_year_id' => $activeFy->id,
                'customer_id' => $validated['customer_id'],
                'quotation_no' => $quotationNo,
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'note' => $validated['note'] ?? null,
                'subtotal' => $amounts['subtotal'],
                'discount' => $amounts['discount'],
                'total_vat' => $amounts['totalVat'],
                'grand_total' => $amounts['grandTotal'],
                'status' => Quotation::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $this->replaceItems($quotation, $amounts['lineItems']);

            return $quotation;
        });

        return redirect()->route('company.quotations.show', $quotation)->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $quotation = $this->owned($quotation)->load(['customer', 'items.product.unit', 'items.service', 'salesInvoice', 'approver', 'converter']);

        return view('company.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $quotation = $this->owned($quotation)->load('items');
        abort_unless($quotation->status === Quotation::STATUS_DRAFT, 409, 'Only Draft quotations may be edited.');

        return view('company.quotations.edit', array_merge($this->formData(), compact('quotation')));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $companyId = (int) auth()->user()->company_id;
        $validated = $this->validateQuotation($request, $companyId);

        DB::transaction(function () use ($request, $quotation, $validated, $companyId): void {
            $quotation = Quotation::where('company_id', $companyId)->lockForUpdate()->findOrFail($quotation->id);
            if ($quotation->status !== Quotation::STATUS_DRAFT) {
                abort(409, 'Only Draft quotations may be edited.');
            }

            $amounts = app(SalesController::class)->calculateStoreAmounts($request, $companyId);
            $quotation->update([
                'customer_id' => $validated['customer_id'], 'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'] ?? null, 'reference_no' => $validated['reference_no'] ?? null,
                'note' => $validated['note'] ?? null, 'subtotal' => $amounts['subtotal'],
                'discount' => $amounts['discount'], 'total_vat' => $amounts['totalVat'],
                'grand_total' => $amounts['grandTotal'],
            ]);
            $quotation->items()->delete();
            $this->replaceItems($quotation, $amounts['lineItems']);
        });

        return redirect()->route('company.quotations.show', $quotation)->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Quotation $quotation)
    {
        $companyId = (int) auth()->user()->company_id;

        DB::transaction(function () use ($quotation, $companyId): void {
            $quotation = Quotation::where('company_id', $companyId)->lockForUpdate()->findOrFail($quotation->id);
            abort_unless($quotation->status === Quotation::STATUS_DRAFT, 409, 'Only Draft quotations may be deleted.');
            $quotation->delete();
        });

        return redirect()->route('company.quotations.index')->with('success', 'Draft quotation deleted.');
    }

    public function approve(Quotation $quotation)
    {
        $companyId = (int) auth()->user()->company_id;

        DB::transaction(function () use ($quotation, $companyId): void {
            $quotation = Quotation::with('items')->where('company_id', $companyId)->lockForUpdate()->findOrFail($quotation->id);
            abort_unless($quotation->status === Quotation::STATUS_DRAFT, 409, 'Only Draft quotations may be approved.');
            $this->assertIntegrity($quotation);
            $quotation->update([
                'status' => Quotation::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('company.quotations.show', $quotation)->with('success', 'Quotation approved.');
    }

    public function convert(Request $request, Quotation $quotation)
    {
        $validated = $request->validate(['invoice_date' => ['required', 'date_format:Y-m-d']]);
        $companyId = (int) auth()->user()->company_id;

        try {
            $invoice = DB::transaction(function () use ($quotation, $validated, $companyId) {
                $quotation = Quotation::with('items')->where('company_id', $companyId)->lockForUpdate()->findOrFail($quotation->id);
                if ($quotation->status !== Quotation::STATUS_APPROVED || $quotation->sales_invoice_id !== null) {
                    throw new RuntimeException('Only an unconverted Approved quotation may generate an invoice.');
                }
                $this->assertIntegrity($quotation);

                $activeFy = $this->activeFinancialYear($companyId, true);
                $invoiceNo = InvoiceNumberService::generate('SI', $companyId, $activeFy->id, SalesInvoice::class, 'invoice_no');
                session(['pending_sales_invoice' => ['invoice_no' => $invoiceNo, 'company_id' => $companyId, 'financial_year_id' => $activeFy->id]]);

                $payload = [
                    'customer_id' => $quotation->customer_id,
                    'sale_date' => $validated['invoice_date'],
                    'item_type' => $quotation->items->pluck('item_type')->all(),
                    'product_id' => $quotation->items->pluck('product_id')->all(),
                    'service_id' => $quotation->items->pluck('service_id')->all(),
                    'quantity' => $quotation->items->pluck('quantity')->all(),
                    'unit_price' => $quotation->items->pluck('unit_price')->all(),
                    'vat_rate' => $quotation->items->pluck('vat_rate')->all(),
                    'discount_amount' => $quotation->discount,
                    'paid_amount' => 0,
                    'note' => trim('Generated from quotation ' . $quotation->quotation_no . '. ' . ($quotation->note ?? '')),
                ];

                $salesRequest = Request::create(route('company.sales.store'), 'POST', $payload);
                $salesRequest->setLaravelSession(session()->driver());
                $salesRequest->setUserResolver(fn () => auth()->user());
                app(SalesController::class)->store($salesRequest);

                $invoice = SalesInvoice::where('company_id', $companyId)->where('financial_year_id', $activeFy->id)
                    ->where('invoice_no', $invoiceNo)->first();
                if (! $invoice) {
                    throw new RuntimeException(session('error', 'Sales invoice generation failed.'));
                }

                $quotation->update([
                    'status' => Quotation::STATUS_CONVERTED,
                    'sales_invoice_id' => $invoice->id,
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
                ]);

                return $invoice;
            });
        } catch (\Throwable $exception) {
            session()->forget('pending_sales_invoice');
            $safeMessages = [
                'Only an unconverted Approved quotation may generate an invoice.',
                'Quotation integrity validation failed.',
                'Quotation contains an invalid company item.',
                'Quotation customer does not belong to the company.',
                'No active financial year found for selected sale date.',
                'Insufficient stock.',
            ];
            $message = in_array($exception->getMessage(), $safeMessages, true)
                ? $exception->getMessage()
                : 'Sales invoice generation failed.';

            return back()->withInput()->with('error', $message);
        }

        return redirect()->route('company.sales.show', $invoice)->with('success', 'Quotation converted to Sales Invoice.');
    }

    public function print(Quotation $quotation)
    {
        $quotation = $this->owned($quotation)->load(['customer', 'items.product.unit', 'items.service', 'salesInvoice']);

        return view('company.quotations.print', compact('quotation'));
    }

    private function validateQuotation(Request $request, int $companyId): array
    {
        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'quotation_date' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:quotation_date'],
            'reference_no' => ['nullable', 'string', 'max:255'], 'note' => ['nullable', 'string', 'max:5000'],
            'item_type' => ['required', 'array', 'min:1'], 'item_type.*' => ['required', Rule::in(['product', 'service'])],
            'product_id' => ['required', 'array'], 'service_id' => ['required', 'array'],
            'quantity' => ['required', 'array'], 'quantity.*' => ['required', 'numeric', 'min:1'],
            'unit_price' => ['required', 'array'], 'unit_price.*' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'array'], 'vat_rate.*' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function formData(): array
    {
        $companyId = (int) auth()->user()->company_id;
        $activeFy = $this->activeFinancialYear($companyId);

        return [
            'activeFy' => $activeFy,
            'customers' => Customer::where('company_id', $companyId)->orderBy('name')->get(),
            'products' => Product::with(['unit', 'vat'])->where('company_id', $companyId)->orderBy('name')->get(),
            'services' => Service::with('vat')->where('company_id', $companyId)->orderBy('name')->get(),
            'vats' => Vat::where('company_id', $companyId)->where('status', 'active')->get(),
        ];
    }

    private function activeFinancialYear(int $companyId, bool $lock = false): FinancialYear
    {
        $query = FinancialYear::where('company_id', $companyId)->where('is_active', 1);
        if ($lock) $query->lockForUpdate();

        return $query->firstOrFail();
    }

    private function replaceItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $quotation->items()->create(array_merge($item, ['company_id' => $quotation->company_id]));
        }
    }

    private function assertIntegrity(Quotation $quotation): void
    {
        if ($quotation->items->isEmpty() || (float) $quotation->grand_total <= 0) {
            throw new RuntimeException('Quotation integrity validation failed.');
        }
        foreach ($quotation->items as $item) {
            $query = $item->item_type === 'product' ? Product::query() : Service::query();
            $id = $item->item_type === 'product' ? $item->product_id : $item->service_id;
            if (! $id || ! $query->where('company_id', $quotation->company_id)->whereKey($id)->exists()) {
                throw new RuntimeException('Quotation contains an invalid company item.');
            }
        }
        if (! Customer::where('company_id', $quotation->company_id)->whereKey($quotation->customer_id)->exists()) {
            throw new RuntimeException('Quotation customer does not belong to the company.');
        }

        $subtotal = '0.0000';
        $vat = '0.0000';
        foreach ($quotation->items as $item) {
            $lineAmount = round((float) $item->quantity * (float) $item->unit_price, 2);
            $lineVat = round($lineAmount * ((float) $item->vat_rate / 100), 2);
            if (abs((float) $item->vat_amount - $lineVat) > 0.0001
                || abs((float) $item->total_price - ($lineAmount + $lineVat)) > 0.0001) {
                throw new RuntimeException('Quotation integrity validation failed.');
            }
            $subtotal = (string) round((float) $subtotal + $lineAmount, 2);
            $vat = (string) round((float) $vat + $lineVat, 2);
        }
        $grandTotal = round((float) $subtotal + (float) $vat - (float) $quotation->discount, 2);
        if (abs((float) $quotation->subtotal - (float) $subtotal) > 0.0001
            || abs((float) $quotation->total_vat - (float) $vat) > 0.0001
            || abs((float) $quotation->grand_total - $grandTotal) > 0.0001) {
            throw new RuntimeException('Quotation integrity validation failed.');
        }
    }

    private function owned(Quotation $quotation): Quotation
    {
        abort_unless((int) $quotation->company_id === (int) auth()->user()->company_id, 404);
        return $quotation;
    }
}
