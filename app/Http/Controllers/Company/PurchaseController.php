<?php

namespace App\Http\Controllers\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Vat;
use App\Models\Account;
use App\Models\PurchasePayment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use App\Services\AccountBalanceService;
use App\Services\SupplierTransactionService;
use App\Models\SupplierTransaction;
use App\Models\StockMovement;

use App\Services\ValidationService;
class PurchaseController extends Controller

{

public function index(Request $request)
{
    $companyId = auth()->user()->company_id;


    $suppliers = Supplier::where(
        'company_id',
        $companyId
    )->get();

   
    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->latest('id')
    ->get();

    
    $activeFy = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->where(
        'is_active',
        1
    )
    ->first();


    $query = PurchaseInvoice::with([
        'supplier',
        'items',
    ])
    ->withCount([
        'payments as active_payments_count' => function ($payment) {
            $payment->where('status', 1);
        },
    ])
    ->where(
        'company_id',
        $companyId
    );


    if ($request->search)
    {
        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->where(
                'invoice_no',
                'like',
                "%{$search}%"
            )

            ->orWhereHas(
                'supplier',
                function ($supplier) use ($search, $companyId) {

                    $supplier->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );

                }
            );

        });
    }

    /**
     * SUPPLIER FILTER
     */
    if ($request->supplier_id)
    {
        $query->where(
            'supplier_id',
            $request->supplier_id
        );
    }

    /**
     * STATUS FILTER
     */
    if (!$request->has('status'))
    {
        $query->where(
            'status',
            1
        );
    }
    elseif ($request->filled('status'))
    {
        $query->where(
            'status',
            $request->status
        );
    }

    /**
     * PAYMENT STATUS FILTER
     */
    if ($request->filled('payment_status'))
    {
        $query->where(
            'payment_status',
            $request->payment_status
        );
    }

    /**
     * FINANCIAL YEAR FILTER
     */

    if (!$request->has('financial_year_id'))
    {
        // First page load

        if ($activeFy)
        {
            $query->where(
                'financial_year_id',
                $activeFy->id
            );

            $startDate = $activeFy->start_date;
            $endDate   = $activeFy->end_date;
        }
        else
        {
            $startDate = null;
            $endDate   = null;
        }
    }
    else
    {
        // User searched

        if ($request->financial_year_id)
        {
            $query->where(
                'financial_year_id',
                $request->financial_year_id
            );
        }

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
    }



    if ($startDate)
    {
        $query->whereDate(
            'purchase_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'purchase_date',
            '<=',
            $endDate
        );
    }

    /**
     * SUMMARY TOTALS
     * (Active records only — FY §10B: cancelled records may display but never sum)
     */
    $summaryQuery = (clone $query)->where('status', 1);

    $totalAmount = $summaryQuery->sum('grand_total');
    $totalPaid   = $summaryQuery->sum('paid_amount');
    $totalDue    = $summaryQuery->sum('due_amount');

    $allowedPerPage = [10, 20, 100, 200];

    $perPage = (int) $request->get('per_page', 10);

    if (!in_array($perPage, $allowedPerPage, true))
    {
        $perPage = 10;
    }

    $invoices = $query
        ->latest()
        ->paginate($perPage)
        ->withQueryString();

    app(PurchaseReturnController::class)
        ->attachReturnableQuantityToInvoices($invoices, $companyId);

    $activeReturnInvoiceIds = PurchaseReturn::where('company_id', $companyId)
        ->whereIn('purchase_invoice_id', $invoices->pluck('id'))
        ->where('status', 1)
        ->pluck('purchase_invoice_id')
        ->unique()
        ->all();

    return view(
        'company.purchases.index',
        compact(
            'invoices',
            'suppliers',
            'financialYears',
            'activeFy',
            'startDate',
            'endDate',
            'totalAmount',
            'totalPaid',
            'totalDue',
            'perPage',
            'activeReturnInvoiceIds'
        )
    );
}


public function printList(Request $request)
{
    $companyId = auth()->user()->company_id;

    $suppliers = Supplier::where(
        'company_id',
        $companyId
    )->get();

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->latest('id')
    ->get();

    $activeFy = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->where(
        'is_active',
        1
    )
    ->first();

    $query = PurchaseInvoice::with([
        'supplier',
    ])
    ->where(
        'company_id',
        $companyId
    );

    if ($request->search)
    {
        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->where(
                'invoice_no',
                'like',
                "%{$search}%"
            )

            ->orWhereHas(
                'supplier',
                function ($supplier) use ($search, $companyId) {

                    $supplier->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );

                }
            );

        });
    }


    if ($request->supplier_id)
    {
        $query->where(
            'supplier_id',
            $request->supplier_id
        );
    }

    if (!$request->has('status'))
    {
        $query->where(
            'status',
            1
        );
    }
    elseif ($request->filled('status'))
    {
        $query->where(
            'status',
            $request->status
        );
    }

    if ($request->filled('payment_status'))
    {
        $query->where(
            'payment_status',
            $request->payment_status
        );
    }

    if (!$request->has('financial_year_id'))
    {
        if ($activeFy)
        {
            $query->where(
                'financial_year_id',
                $activeFy->id
            );

            $startDate = $activeFy->start_date;
            $endDate   = $activeFy->end_date;
        }
        else
        {
            $startDate = null;
            $endDate   = null;
        }
    }
    else
    {
        if ($request->financial_year_id)
        {
            $query->where(
                'financial_year_id',
                $request->financial_year_id
            );
        }

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
    }

    if ($startDate)
    {
        $query->whereDate(
            'purchase_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'purchase_date',
            '<=',
            $endDate
        );
    }

    $totalsQuery = (clone $query)->where('status', 1);

    $totalAmount    = (clone $totalsQuery)->sum('grand_total');
    $totalPaid      = (clone $totalsQuery)->sum('paid_amount');
    $totalDue       = (clone $totalsQuery)->sum('due_amount');
    $totalCount     = (clone $query)->count();
    $activeCount    = (clone $query)->where('status', 1)->count();
    $cancelledCount = (clone $query)->where('status', 0)->count();

    $invoices = $query
        ->latest()
        ->get();

    return view(
        'company.purchases.print-list',
        compact(
            'invoices',
            'suppliers',
            'financialYears',
            'activeFy',
            'startDate',
            'endDate',
            'totalAmount',
            'totalPaid',
            'totalDue',
            'totalCount',
            'activeCount',
            'cancelledCount'
        )
    );
}


public function create()
{
    $companyId =
        auth()->user()->company_id;

    $activeFy = FinancialYear::where(
            'company_id',
            $companyId
        )
        ->where(
            'is_active',
            1
        )
        ->first();

    if (!$activeFy)
    {
        return back()->with(
            'error',
            'Please activate financial year first.'
        );
    }

    
    

    $invoiceNo = DB::transaction(function () use ($companyId, $activeFy) {
        return InvoiceNumberService::generate(
            'PU',
            $companyId,
            $activeFy->id,
            PurchaseInvoice::class,
            'invoice_no'
        );
    });

    session([
        'pending_purchase_invoice' => [
            'invoice_no'          => $invoiceNo,
            'company_id'          => $companyId,
            'financial_year_id'   => $activeFy->id,
        ],
    ]);
   

    $suppliers = Supplier::where(
            'company_id',
            $companyId
        )
        ->get();

    $products = Product::with([
            'unit',
            'vat',
        ])
        ->where(
            'company_id',
            $companyId
        )
        ->get();

    $services = Service::with([
            'vat',
        ])
        ->where(
            'company_id',
            $companyId
        )
        ->get();

    $vats = Vat::where(
            'company_id',
            $companyId
        )
        ->get();

    $accounts = Account::where(
            'company_id',
            $companyId
        )
        ->where(
            'status',
            'active'
        )
        ->get();

    return view(
        'company.purchases.create',
        compact(
            'suppliers',
            'products',
            'services',
            'vats',
            'accounts',
            'invoiceNo',
            'activeFy'
        )
    );
}

/**
 * Store
 */

public function store(Request $request)
{
$companyId =
auth()->user()->company_id;

$request->validate([

    'supplier_id' =>
        'required|exists:suppliers,id,company_id,' .
        $companyId,

    'purchase_date' =>
        'required|date',

    'item_type' =>
        'required|array|min:1',

    'item_type.*' =>
        'required|in:product,service',

    'quantity' =>
        'required|array',

    'quantity.*' =>
        'required|numeric|min:1',

    'unit_price' =>
        'required|array',

    'unit_price.*' =>
        'required|numeric|min:0',

    'paid_amount' =>
        'nullable|numeric|min:0',

    'account_id' =>
        'nullable|exists:accounts,id,company_id,' .
        $companyId,

]);

if (
    $request->paid_amount > 0
    &&
    !$request->account_id
) {
    return back()->withErrors([

        'account_id' =>
            'Select payment account.'

    ]);
}

try {

    $amounts = $this->calculateStoreAmounts($request, $companyId);

    $paidAmount = round((float) ($request->paid_amount ?? 0), 2);

    if ($paidAmount > $amounts['grandTotal']) {
        throw new \Exception('Paid amount cannot exceed the invoice grand total.');
    }

    $invoice = DB::transaction(function ()
    use ($request, $companyId, $amounts) {

        $activeFy =
            FinancialYear::where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                1
            )
            ->firstOrFail();

        $purchaseDate =
            \Carbon\Carbon::parse(
                $request->purchase_date
            );

        $startDate =
            \Carbon\Carbon::parse(
                $activeFy->start_date
            );

        $endDate =
            \Carbon\Carbon::parse(
                $activeFy->end_date
            );

        if (
            $purchaseDate->lt($startDate)
            ||
            $purchaseDate->gt($endDate)
        ) {
            throw new \Exception(
                'No active financial year found for selected purchase date.'
            );
        }

        $grandTotal = $amounts['grandTotal'];

        $paidAmount =
            $request->paid_amount ?? 0;

        $dueAmount =
            max(
                0,
                $grandTotal - $paidAmount
            );

        $paymentStatus =
            'unpaid';

        if (
            $paidAmount >=
            $grandTotal
        ) {
            $paymentStatus =
                'paid';
        }
        elseif (
            $paidAmount > 0
        ) {
            $paymentStatus =
                'partial';
        }

        $pending = session('pending_purchase_invoice');

        if (
            !is_array($pending)
            || (int) ($pending['company_id'] ?? 0) !== $companyId
            || (int) ($pending['financial_year_id'] ?? 0) !== (int) $activeFy->id
            || empty($pending['invoice_no'])
        ) {
            throw new \Exception(
                'Please reopen the purchase invoice create form and try again.'
            );
        }

        $invoiceNo = (string) $pending['invoice_no'];

        $expectedPrefix = sprintf(
            'PU-%d-%d-',
            $companyId,
            $activeFy->id
        );

        if (!str_starts_with($invoiceNo, $expectedPrefix)) {
            throw new \Exception(
                'Invalid invoice number. Please reopen the purchase invoice create form and try again.'
            );
        }

        $invoiceTaken = PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->where('invoice_no', $invoiceNo)
            ->lockForUpdate()
            ->exists();

        if ($invoiceTaken) {
            throw new \Exception(
                'Invoice number conflict. Please reopen the purchase invoice create form and try again.'
            );
        }

        $supplier = Supplier::where('company_id', $companyId)
            ->findOrFail($request->supplier_id);

        $dueDate = $this->calculateInvoiceDueDate(
            $request->purchase_date,
            $supplier
        );

        $invoice =
            PurchaseInvoice::create([

                'created_by' =>
                    auth()->id(),

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'supplier_id' =>
                    $request->supplier_id,

                'invoice_no' =>
                    $invoiceNo,

                'purchase_date' =>
                    $request->purchase_date,

                'due_date' =>
                    $dueDate,

                'subtotal' =>
                    $amounts['subtotal'],

                'discount' =>
                    $amounts['discount'],

                'total_vat' =>
                    $amounts['totalVat'],

                'grand_total' =>
                    $grandTotal,

                'paid_amount' =>
                    $paidAmount,

                'due_amount' =>
                    $dueAmount,

                'payment_status' =>
                    $paymentStatus,

                'note' =>
                    $request->note,

                'status' => 1,

            ]);

        session()->forget('pending_purchase_invoice');
    


        /*
        |--------------------------------------------------------------------------
        | PURCHASE PAYMENT
        |--------------------------------------------------------------------------
        */

        if (
            $paidAmount > 0
            &&
            $request->account_id
        ) 
        {

            $paymentNo =
                InvoiceNumberService::generate(
                    'PP',
                    $companyId,
                    $activeFy->id,
                    PurchasePayment::class,
                    'payment_no'
                );

            $payment =
                PurchasePayment::create([

                    'company_id' =>
                        $companyId,

                    'financial_year_id' =>
                        $activeFy->id,

                    'purchase_invoice_id' =>
                        $invoice->id,

                    'supplier_id' =>
                        $invoice->supplier_id,

                    'account_id' =>
                        $request->account_id,

                    'payment_no' =>
                        $paymentNo,

                    'payment_date' =>
                        $request->purchase_date,

                    'amount' =>
                        $paidAmount,

                    'payment_method' =>
                        'invoice',

                    'note' =>
                        'Auto payment from purchase invoice',

                    'created_by' =>
                        auth()->id(),

                    'status' => 1,

                ]);

            AccountBalanceService::createTransaction([

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'account_id' =>
                    $request->account_id,

                'transaction_date' =>
                    $request->purchase_date,

                'voucher_no' =>
                    $paymentNo,

                'reference_type' =>
                    'purchase_payment',

                'reference_id' =>
                    $payment->id,

                'description' =>
                    'Purchase Payment',

                'debit' =>
                    0,

                'credit' =>
                    $paidAmount,

            ]);
        


    

    SupplierTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'supplier_id'       => $invoice->supplier_id,

    'transaction_date'  => $request->purchase_date,

    'voucher_no'        => $paymentNo,

    'reference_type'    => 'purchase_payment',

    'reference_id'      => $payment->id,

    'reference_no'      => $paymentNo,

    'description'       => 'Purchase Payment',

    'debit'             => $paidAmount,

    'credit'            => 0,

    'created_by'        => auth()->id(),
   'status' => 1,

]);
        }

        
    SupplierTransactionService::createTransaction([

        'company_id' => $companyId,

        'financial_year_id' => $activeFy->id,

        'supplier_id' => $invoice->supplier_id,

        'transaction_date' => $request->purchase_date,

        'voucher_no' => $invoice->invoice_no,

        'reference_type' => 'purchase_invoice',

        'reference_id' => $invoice->id,

        'reference_no' => $invoice->invoice_no,

        'description' => 'Purchase Invoice',

       'debit' => 0,

        'credit' => $invoice->grand_total,

        'created_by' => auth()->id(),

        'status' => 1,

    ]);



        /*
        |--------------------------------------------------------------------------
        | PURCHASE ITEMS
        |--------------------------------------------------------------------------
        */

        foreach ($amounts['lineItems'] as $lineItem) {

            $qty = $lineItem['quantity'];

            $price = $lineItem['unit_price'];

            $vatRate = $lineItem['vat_rate'];

            $vatAmount = $lineItem['vat_amount'];

            $totalPrice = $lineItem['total_price'];

            $type = $lineItem['item_type'];

            $productId = $lineItem['product_id'];

            $serviceId = $lineItem['service_id'];

            PurchaseItem::create([

                'created_by' =>
                    auth()->id(),

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'purchase_invoice_id' =>
                    $invoice->id,

                'item_type' =>
                    $type,

                'product_id' =>
                    $productId,

                'service_id' =>
                    $serviceId,

                'quantity' =>
                    $qty,

                'unit_price' =>
                    $price,

                'price' =>
                    $price,

                'vat_rate' =>
                    $vatRate,

                'vat_amount' =>
                    $vatAmount,

                'total_price' =>
                    $totalPrice,

                'total' =>
                    $totalPrice,

                'status' => 1,

            ]);

            if (
                $type == 'product'
                &&
                $productId
            ) {

                $product =
                    Product::where(
                        'company_id',
                        $companyId
                    )
                    ->lockForUpdate()
                    ->findOrFail(
                        $productId
                    );

                StockService::increase(

    $product,

    $qty,

    'purchase',

    $invoice->invoice_no,

    $activeFy->id,

    $request->purchase_date

);
            }
        }

        return $invoice;

    });

    return redirect()
        ->route(
            'company.purchases.show',
            $invoice->id
        )
        ->with(
            'success',
            'Purchase invoice created successfully.'
        );

} catch (\Throwable $e) {
        $safeMessages = [
            'Paid amount cannot exceed the invoice grand total.',
            'No active financial year found for selected purchase date.',
            'Quantity cannot be negative.',
            'Unit price cannot be negative.',
            'VAT rate cannot be negative.',
            'VAT amount cannot be negative.',
            'Discount cannot be negative.',
            'Discount cannot exceed gross total.',
            'Grand total must be greater than zero.',
            'Insufficient stock.',
            'Invalid quantity',
            'Financial Year is required for stock transaction.',
            'Insufficient account balance.',
            'Product is required for product lines.',
            'Service is required for service lines.',
            'Please reopen the purchase invoice create form and try again.',
            'Invalid invoice number. Please reopen the purchase invoice create form and try again.',
            'Invoice number conflict. Please reopen the purchase invoice create form and try again.',
        ];

        $this->logPurchaseException('Purchase invoice store failed.', $e, [
            'supplier_id' => $request->supplier_id,
            'purchase_date'     => $request->purchase_date,
        ]);

        $error = $this->resolveSafeExceptionMessage(
            $e,
            $safeMessages,
            'Unable to save purchase invoice. Please try again.'
        );

        return back()
            ->withInput()
            ->with('error', $error);
}


}

protected function calculateStoreAmounts(Request $request, int $companyId): array
{
    $lineItems = [];
    $subtotal = 0;
    $totalVat = 0;

    foreach ($request->item_type as $key => $type) {
        $qty = (float) $request->quantity[$key];
        $price = (float) $request->unit_price[$key];
        $vatRate = (float) ($request->vat_rate[$key] ?? 0);

        if ($qty < 0) {
            throw new \Exception('Quantity cannot be negative.');
        }

        if ($price < 0) {
            throw new \Exception('Unit price cannot be negative.');
        }

        if ($vatRate < 0) {
            throw new \Exception('VAT rate cannot be negative.');
        }

        $lineAmount = round($qty * $price, 2);
        $vatAmount = round($lineAmount * ($vatRate / 100), 2);
        $lineTotal = round($lineAmount + $vatAmount, 2);

        if ($vatAmount < 0) {
            throw new \Exception('VAT amount cannot be negative.');
        }

        [$productId, $serviceId] = $this->normalizeStoreLineItemIds(
            $type,
            $request->product_id[$key] ?? null,
            $request->service_id[$key] ?? null,
            $companyId
        );

        $subtotal = round($subtotal + $lineAmount, 2);
        $totalVat = round($totalVat + $vatAmount, 2);

        $lineItems[] = [
            'item_type'   => $type,
            'quantity'    => $qty,
            'unit_price'  => $price,
            'vat_rate'    => $vatRate,
            'vat_amount'  => $vatAmount,
            'total_price' => $lineTotal,
            'product_id'  => $productId,
            'service_id'  => $serviceId,
        ];
    }

    $grossTotal = round($subtotal + $totalVat, 2);

    $discount = round((float) ($request->discount_amount ?? 0), 2);

    if ($discount < 0) {
        throw new \Exception('Discount cannot be negative.');
    }

    if ($discount > $grossTotal) {
        throw new \Exception('Discount cannot exceed gross total.');
    }

    $grandTotal = round($grossTotal - $discount, 2);

    if ($grandTotal <= 0) {
        throw new \Exception('Grand total must be greater than zero.');
    }

    return [
        'lineItems'      => $lineItems,
        'subtotal'       => $subtotal,
        'discount'       => $discount,
        'grossTotal'     => $grossTotal,
        'totalVat'       => $totalVat,
        'grandTotal'     => $grandTotal,
    ];
}

protected function normalizeStoreLineItemIds(
    string $type,
    mixed $productId,
    mixed $serviceId,
    int $companyId
): array {
    if ($type === 'product') {
        if (empty($productId)) {
            throw new \Exception('Product is required for product lines.');
        }

        $validProduct = Product::where('company_id', $companyId)
            ->where('id', $productId)
            ->exists();

        if (!$validProduct) {
            throw new \Exception('Product is required for product lines.');
        }

        return [(int) $productId, null];
    }

    if ($type === 'service') {
        if (empty($serviceId)) {
            throw new \Exception('Service is required for service lines.');
        }

        $validService = Service::where('company_id', $companyId)
            ->where('id', $serviceId)
            ->exists();

        if (!$validService) {
            throw new \Exception('Service is required for service lines.');
        }

        return [null, (int) $serviceId];
    }

    throw new \Exception('Product is required for product lines.');
}

public function cancel(Request $request, $id)
{
    $companyId = auth()->user()->company_id;

    $request->validate([
        'cancel_date' =>
            ValidationService::requiredDate(),
        'cancel_reason' =>
            ValidationService::requiredString(500),
    ]);

    try {
        DB::transaction(function () use ($request, $id, $companyId) {

            $activeFy = FinancialYear::where('company_id', $companyId)
                ->where('is_active', 1)
                ->firstOrFail();

            $cancelDate = \Carbon\Carbon::parse($request->cancel_date);
            $startDate  = \Carbon\Carbon::parse($activeFy->start_date);
            $endDate    = \Carbon\Carbon::parse($activeFy->end_date);

            if ($cancelDate->lt($startDate) || $cancelDate->gt($endDate))
            {
                throw new \Exception(
                    'Cancel date must belong to the active financial year.'
                );
            }

            $cancelBusinessDate = $cancelDate->toDateString();
            $cancelReason = trim($request->cancel_reason);

            $invoice = PurchaseInvoice::where('company_id', $companyId)
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($invoice->status == 0)
            {
                throw new \Exception('Purchase Already Cancelled.');
            }

            if ((int) $invoice->financial_year_id !== (int) $activeFy->id) {
                throw new \Exception(
                    'This Purchase Invoice belongs to another Financial Year. Please activate that Financial Year first.'
                );
            }

            $activePaymentsExist = PurchasePayment::where('company_id', $companyId)
                ->where('purchase_invoice_id', $invoice->id)
                ->where('status', 1)
                ->exists();

            if ($activePaymentsExist)
            {
                throw new \Exception(
                    'Invoice cannot be cancelled because one or more active payments exist.'
                );
            }

            $activeReturnsExist = PurchaseReturn::where('company_id', $companyId)
                ->where('purchase_invoice_id', $invoice->id)
                ->where('status', 1)
                ->exists();

            if ($activeReturnsExist)
            {
                throw new \Exception(
                    'This invoice cannot be cancelled because one or more active purchase returns exist.'
                );
            }

            foreach ($invoice->items as $item)
            {
                if (!$item->product_id)
                {
                    continue;
                }

                StockService::decrease(
                    $item->product,
                    $item->quantity,
                    'purchase_cancel',
                    $invoice->invoice_no,
                    $activeFy->id,
                    $cancelBusinessDate,
                    $item->unit_price,
                    'Purchase Cancel: ' . $cancelReason
                );
            }

            $transactions = SupplierTransaction::where(
                'company_id',
                $companyId
            )
            ->where(
                'reference_type',
                'purchase_invoice'
            )
            ->where(
                'reference_id',
                $invoice->id
            )
            ->where(
                'status',
                1
            )
            ->get();

            if (!$transactions->isEmpty()) {

                $cancelReferenceType = 'purchase_invoice_cancel';

                $description = 'Purchase Invoice Cancel' . ($cancelReason ? ': ' . $cancelReason : '');

                foreach ($transactions as $transaction) {

                    SupplierTransactionService::createTransaction([

                        'company_id' =>

                            $transaction->company_id,

                        'supplier_id' =>

                            $transaction->supplier_id,

                        'financial_year_id' =>

                            $activeFy->id,

                        'transaction_date' =>

                            $cancelBusinessDate,

                        'voucher_no' =>

                            'REV-' .
                            $transaction->voucher_no,

                        'reference_type' =>

                            $cancelReferenceType,

                        'reference_id' =>

                            $transaction->reference_id,

                        'reference_no' =>

                            $transaction->reference_no,

                        'description' =>

                            $description,

                        'debit' =>

                            $transaction->credit,

                        'credit' =>

                            $transaction->debit,

                        'created_by' =>

                            auth()->id(),

                        'status' => 1,

                    ]);

                }

            }

            $invoice->update([
                'status' => 0,
                'note' => trim(($invoice->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
            ]);
        });

        return back()->with('success', 'Purchase cancelled successfully.');
    }
    catch (\Throwable $e)
    {
        $safeMessages = [
            'Purchase Already Cancelled.',
            'This Purchase Invoice belongs to another Financial Year. Please activate that Financial Year first.',
            'Invoice cannot be cancelled because one or more active payments exist.',
            'This invoice cannot be cancelled because one or more active purchase returns exist.',
            'Cancel date must belong to the active financial year.',
        ];

        $this->logPurchaseException('Purchase invoice cancel failed.', $e, [
            'invoice_id' => $id,
        ]);

        $error = $this->resolveSafeExceptionMessage(
            $e,
            $safeMessages,
            'Unable to cancel invoice. Please try again.'
        );

        return back()->with('error', $error);
    }
}

/**
 * PRINT
 */

public function print($id)
{
    $invoice = PurchaseInvoice::with([
            'supplier',
            'items.product.unit',
            'items.service',
        ])
        ->where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

    return view(
        'company.purchases.print',
        compact(
            'invoice'
        )
    );
}


    /**
     * SHOW
     */
 public function show($id)
{
    $companyId = auth()->user()->company_id;

    $invoice = PurchaseInvoice::with([

        'supplier',

        'items.product.unit',

        'items.service',

        'company',

    ])
    ->where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    return view(
        'company.purchases.show',
        compact(
            'invoice'
        )
    );
}

public function edit($id)
{
    $companyId = auth()->user()->company_id;

    $invoice = PurchaseInvoice::with([
            'supplier',
            'items.product.unit',
            'items.service',
            'financialYear',
            'payments.account',
        ])
        ->where('company_id', $companyId)
        ->findOrFail($id);

    if ($invoice->status == 0)
    {
        return redirect()
            ->route('company.purchases.index')
            ->with('error', 'Cancelled invoice cannot be edited.');
    }

    $activeFy = FinancialYear::where('company_id', $companyId)
        ->where('is_active', 1)
        ->first();

    if (!$activeFy) {
        return redirect()
            ->route('company.purchases.index')
            ->with('error', 'Please activate financial year first.');
    }

    if ((int) $invoice->financial_year_id !== (int) $activeFy->id) {
        return redirect()
            ->route('company.purchases.index')
            ->with('error', 'Purchase Invoice belongs to another Financial Year.');
    }

    return view(
        'company.purchases.edit',
        compact('invoice')
    );
}

public function update(Request $request, $id)
{
    $companyId = auth()->user()->company_id;

    $request->validate([
        'purchase_date' => 'required|date',
        'note' => 'nullable|string',
    ]);

    try {
        DB::transaction(function () use ($request, $id, $companyId) {

            $invoice = PurchaseInvoice::where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($invoice->status == 0)
            {
                throw new \Exception('Cancelled invoice cannot be edited.');
            }

            $activeFy = FinancialYear::where('company_id', $companyId)
                ->where('is_active', 1)
                ->first();

            if (!$activeFy) {
                throw new \Exception('Please activate financial year first.');
            }

            if ((int) $invoice->financial_year_id !== (int) $activeFy->id) {
                throw new \Exception(
                    'Purchase Invoice belongs to another Financial Year.'
                );
            }

            $purchaseDateLocked = $this->invoicePurchaseDateIsLocked(
                $invoice,
                $companyId
            );

            if ($purchaseDateLocked) {
                $requestedPurchaseDate = \Carbon\Carbon::parse($request->purchase_date)
                    ->format('Y-m-d');

                $currentPurchaseDate = $invoice->purchase_date
                    ? \Carbon\Carbon::parse($invoice->purchase_date)->format('Y-m-d')
                    : null;

                if ($requestedPurchaseDate !== $currentPurchaseDate) {
                    throw new \Exception(
                        'Purchase date cannot be changed because this invoice has linked payment, return, or refund activity.'
                    );
                }

                $invoice->update([
                    'note' => $request->note,
                ]);

                return;
            }

            $purchaseDate = \Carbon\Carbon::parse($request->purchase_date);
            $startDate = \Carbon\Carbon::parse($activeFy->start_date);
            $endDate = \Carbon\Carbon::parse($activeFy->end_date);

            if ($purchaseDate->lt($startDate) || $purchaseDate->gt($endDate)) {
                throw new \Exception('No active financial year found for selected purchase date.');
            }

            $supplier = Supplier::where('company_id', $companyId)
                ->findOrFail($invoice->supplier_id);

            $newPurchaseDate = $purchaseDate->toDateString();
            $currentPurchaseDate = $invoice->purchase_date
                ? \Carbon\Carbon::parse($invoice->purchase_date)->format('Y-m-d')
                : null;

            $invoice->update([
                'purchase_date' => $newPurchaseDate,
                'due_date' => $this->calculateInvoiceDueDate(
                    $newPurchaseDate,
                    $supplier
                ),
                'note' => $request->note,
            ]);

            if ($newPurchaseDate !== $currentPurchaseDate) {
                $this->syncInvoicePurchaseBusinessDate(
                    $invoice,
                    $newPurchaseDate,
                    $companyId
                );
            }
        });
    } catch (\Throwable $e) {
        return back()->with(
            'error',
            $this->resolveSafeExceptionMessage(
                $e,
                [
                    'Cancelled invoice cannot be edited.',
                    'Purchase date cannot be changed because this invoice has linked payment, return, or refund activity.',
                    'Please activate financial year first.',
                    'Purchase Invoice belongs to another Financial Year.',
                    'No active financial year found for selected purchase date.',
                ],
                'Unable to update purchase invoice.'
            )
        );
    }

    return redirect()
        ->route('company.purchases.index')
        ->with('success', 'Purchase invoice updated successfully.');
}

    protected function syncInvoicePurchaseBusinessDate(
        PurchaseInvoice $invoice,
        string $purchaseDate,
        int $companyId
    ): void {
        SupplierTransaction::where('company_id', $companyId)
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->update([
                'transaction_date' => $purchaseDate,
            ]);

        StockMovement::where('company_id', $companyId)
            ->where('financial_year_id', $invoice->financial_year_id)
            ->where('type', 'purchase')
            ->where('reference_no', $invoice->invoice_no)
            ->where('quantity', '>', 0)
            ->update([
                'transaction_date' => $purchaseDate,
            ]);
    }

    protected function invoicePurchaseDateIsLocked(
        PurchaseInvoice $invoice,
        int $companyId
    ): bool {
        if ((int) $invoice->status === 0) {
            return true;
        }

        if (PurchasePayment::where('company_id', $companyId)
            ->where('purchase_invoice_id', $invoice->id)
            ->where('status', 1)
            ->exists()) {
            return true;
        }

        if (PurchaseReturn::where('company_id', $companyId)
            ->where('purchase_invoice_id', $invoice->id)
            ->where('status', 1)
            ->exists()) {
            return true;
        }

        $returnIds = PurchaseReturn::where('company_id', $companyId)
            ->where('purchase_invoice_id', $invoice->id)
            ->pluck('id');

        if ($returnIds->isNotEmpty()
            && PurchaseReturnRefund::where('company_id', $companyId)
                ->whereIn('purchase_return_id', $returnIds)
                ->where('status', 1)
                ->exists()) {
            return true;
        }

        return false;
    }

    protected function calculateInvoiceDueDate(
        string $purchaseDate,
        Supplier $supplier
    ): string {
        return \Carbon\Carbon::parse($purchaseDate)
            ->addDays(max(0, (int) ($supplier->credit_days ?? 0)))
            ->toDateString();
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

        if (str_ends_with($message, ' insufficient stock.')) {
            return 'Insufficient stock available.';
        }

        return $fallback;
    }

    protected function logPurchaseException(
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
