<?php

namespace App\Http\Controllers\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Vat;
use App\Models\Account;
use App\Models\SalesPayment;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use App\Services\AccountBalanceService;
use App\Services\CustomerTransactionService;
use App\Models\AccountTransaction;
class SalesController extends Controller

{

public function index(Request $request)
{
    $companyId = auth()->user()->company_id;


    $customers = Customer::where(
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


    $query = SalesInvoice::with(
        'customer'
    )
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
                'customer',
                function ($customer) use ($search) {

                    $customer->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );

                }
            );

        });
    }

    /**
     * CUSTOMER FILTER
     */
    if ($request->customer_id)
    {
        $query->where(
            'customer_id',
            $request->customer_id
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
            'sale_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'sale_date',
            '<=',
            $endDate
        );
    }

    $invoices = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    return view(
        'company.sales.index',
        compact(
            'invoices',
            'customers',
            'financialYears',
            'startDate',
            'endDate'
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

    
    

$invoiceNo = InvoiceNumberService::generate(
    'SI',
    $companyId,
    $activeFy->id,
    SalesInvoice::class,
    'invoice_no'
);
   

    $customers = Customer::where(
            'company_id',
            $companyId
        )
        ->get();

    $products = Product::where(
            'company_id',
            $companyId
        )
        ->get();

    $services = Service::where(
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
        'company.sales.create',
        compact(
            'customers',
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

    'customer_id' =>
        'required|exists:customers,id,company_id,' .
        $companyId,

    'sale_date' =>
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

    'product_id.*' =>
        'nullable|exists:products,id',

    'service_id.*' =>
        'nullable|exists:services,id',

    'account_id' =>
        'nullable|exists:accounts,id',

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
if (
    $request->paid_amount > 0
    &&
    $request->account_id
)
{
    
    $account = Account::where(
        'company_id',
        $companyId
    )->findOrFail(
        $request->account_id
    );

   
}


    $invoice = DB::transaction(function ()
    use ($request, $companyId) {

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

        $saleDate =
            \Carbon\Carbon::parse(
                $request->sale_date
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
            $saleDate->lt($startDate)
            ||
            $saleDate->gt($endDate)
        ) {
            throw new \Exception(
                'No active financial year found for selected sale date.'
            );
        }

        $grandTotal =
            $request->grand_total;

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

        $invoiceNo =
            InvoiceNumberService::generate(
                'SI',
                $companyId,
                $activeFy->id,
                SalesInvoice::class,
                'invoice_no'
            );

        $invoice =
            SalesInvoice::create([

                'created_by' =>
                    auth()->id(),

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'customer_id' =>
                    $request->customer_id,

                'invoice_no' =>
                    $invoiceNo,

                'sale_date' =>
                    $request->sale_date,

                'subtotal' =>
                    $request->subtotal,

                'discount' =>
                    $request->discount_amount ?? 0,

                'total_vat' =>
                    $request->total_vat,

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
    


        /*
        |--------------------------------------------------------------------------
        | SALES PAYMENT
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
                    'SP',
                    $companyId,
                    $activeFy->id,
                    SalesPayment::class,
                    'payment_no'
                );

            $payment =
                SalesPayment::create([

                    'company_id' =>
                        $companyId,

                    'financial_year_id' =>
                        $activeFy->id,

                    'sales_invoice_id' =>
                        $invoice->id,

                    'customer_id' =>
                        $invoice->customer_id,

                    'account_id' =>
                        $request->account_id,

                    'payment_no' =>
                        $paymentNo,

                    'payment_date' =>
                        $request->sale_date,

                    'paid_amount' =>
                        $paidAmount,

                    'payment_method' =>
                        'invoice',

                    'note' =>
                        'Auto payment from sales invoice',

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
                    $request->sale_date,

                'voucher_no' =>
                    $paymentNo,

                'reference_type' =>
                    'sales_payment',

                'reference_id' =>
                    $payment->id,

                'description' =>
                    'Sales Payment',

                'debit' =>
                    $paidAmount,

                'credit' =>
                    0,

            ]);
        


    


    CustomerTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'customer_id'       => $invoice->customer_id,

    'transaction_date'  => $request->sale_date,

    'voucher_no'        => $paymentNo,

    'reference_type'    => 'sales_payment',

    'reference_id'      => $payment->id,

    'reference_no'      => $paymentNo,

    'description'       => 'Sales Payment',

    'debit'             => 0,

    'credit'            => $paidAmount,

    'created_by'        => auth()->id(),
   'status' => 1,

]);
        }

        
    CustomerTransactionService::createTransaction([

        'company_id' => $companyId,

        'financial_year_id' => $activeFy->id,

        'customer_id' => $invoice->customer_id,

        'transaction_date' => $request->sale_date,

        'voucher_no' => $invoice->invoice_no,

        'reference_type' => 'sales_invoice',

        'reference_id' => $invoice->id,

        'reference_no' => $invoice->invoice_no,

        'description' => 'Sales Invoice',

       'debit' => $invoice->grand_total,

        'credit' => 0,

        'created_by' => auth()->id(),

        'status' => 1,

    ]);



        /*
        |--------------------------------------------------------------------------
        | SALES ITEMS
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->item_type
            as $key => $type
        ) {

            $qty =
                $request->quantity[$key];

            $price =
                $request->unit_price[$key];

            $vatRate =
                $request->vat_rate[$key] ?? 0;

            $vatAmount =
                $request->vat_amount[$key] ?? 0;

            $totalPrice =
                $request->total_price[$key] ?? 0;

            $productId =
                $request->product_id[$key]
                ?? null;

            $serviceId =
                $request->service_id[$key]
                ?? null;

            SalesItem::create([

                'created_by' =>
                    auth()->id(),

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'sales_invoice_id' =>
                    $invoice->id,

                'item_type' =>
                    $type,

                'product_id' =>
                    $productId,

                'service_id' =>
                    $serviceId,

                'quantity' =>
                    $qty,

                'returned_qty' => 0,

                'unit_price' =>
                    $price,

                'vat_rate' =>
                    $vatRate,

                'vat_amount' =>
                    $vatAmount,

                'total_price' =>
                    $totalPrice,

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
                    ->findOrFail(
                        $productId
                    );

                if (
                    $product->current_stock
                    <
                    $qty
                ) {
                    throw new \Exception(
                        $product->name .
                        ' insufficient stock.'
                    );
                }

                StockService::decrease(

    $product,

    $qty,

    'sale',

    $invoice->invoice_no,

    $activeFy->id,

    $request->sale_date

);
            }
        }

        return $invoice;

    });

    return redirect()
        ->route(
            'company.sales.show',
            $invoice->id
        )
        ->with(
            'success',
            'Sales invoice created successfully.'
        );

} catch (\Exception $e) {

    return back()
        ->withInput()
        ->with(
            'error',
            $e->getMessage()
        );
}


}

public function cancel($id)
{
    DB::beginTransaction();

    try {

        $invoice = SalesInvoice::where(
            'company_id',
            auth()->user()->company_id
        )
        ->with(
            'items.product'
        )
        ->findOrFail($id);

        if ($invoice->status == 0)
        {
            throw new \Exception(
                'Sales Already Cancelled.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET PAYMENTS
        |--------------------------------------------------------------------------
        */

        $payments = SalesPayment::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'sales_invoice_id',
            $invoice->id
        )
        ->where(
            'status',
            1
        )
        ->get();

        /*
        |--------------------------------------------------------------------------
        | REVERSE ACCOUNT TRANSACTION
        |--------------------------------------------------------------------------
        */

        foreach ($payments as $payment)
        {
            $accountTransaction =
                AccountTransaction::where(
                    'company_id',
                    auth()->user()->company_id
                )
                ->where(
                    'reference_type',
                    'sales_payment'
                )
                ->where(
                    'reference_id',
                    $payment->id
                )
                ->first();

            if ($accountTransaction)
            {
                AccountBalanceService::reverseTransaction(

                    $accountTransaction,

                    'sales_payment_cancel',

                    'Sales Payment Cancel'

                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK REVERSE
        |--------------------------------------------------------------------------
        */

        foreach ($invoice->items as $item)
        {
            if (
                $item->item_type != 'product'
            )
            {
                continue;
            }

            StockService::increase(

                $item->product,

                $item->quantity,

                'sales_cancel',

                $invoice->invoice_no,

                $invoice->financial_year_id,

                $invoice->sale_date,

                $item->unit_price,

                'Sales Cancel'

            );
        }
                /*
        |--------------------------------------------------------------------------
        | CUSTOMER TRANSACTION DELETE
        |--------------------------------------------------------------------------
        */

        CustomerTransactionService::deleteByReference(

            auth()->user()->company_id,

            'sales_invoice',

            $invoice->id

        );

        foreach ($payments as $payment)
        {
            CustomerTransactionService::deleteByReference(

                auth()->user()->company_id,

                'sales_payment',

                $payment->id

            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT CANCEL
        |--------------------------------------------------------------------------
        */

        SalesPayment::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'sales_invoice_id',
            $invoice->id
        )
        ->update([

            'status' => 0

        ]);

        

        /*
        |--------------------------------------------------------------------------
        | SALES CANCEL
        |--------------------------------------------------------------------------
        */

        $invoice->update([

            'status' => 0,

            'payment_status' => 'cancelled',

            'note' => trim(

                ($invoice->note ?? '') .
                ' [Cancelled]'

            ),

        ]);

        DB::commit();

        return back()->with(

            'success',

            'Sales Cancelled Successfully'

        );

    }
    catch (\Exception $e)
    {
        DB::rollBack();

        return back()->with(

            'error',

            $e->getMessage()

        );
    }
}











/**
 * PRINT
 */

public function print($id)
{
    $invoice = SalesInvoice::with([

            'customer',
            'items.product'

        ])
        ->where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

    return view(
        'company.sales.print',
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
    $invoice = SalesInvoice::with([

        'customer',

        'items.product',

        'items.service',

        'company',

    ])
    ->where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    $accounts = Account::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'status',
        'active'
    )
    ->get();
return view(
'company.sales.show',
compact(
'invoice',
'accounts'
)
);
}
}