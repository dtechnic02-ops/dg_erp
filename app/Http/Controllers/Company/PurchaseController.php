<?php


namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Vat;

use App\Services\AccountBalanceService;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use App\Services\ValidationService;

use App\Services\SupplierTransactionService;


class PurchaseController extends Controller
{
    /**
     * 📦 PURCHASE LIST
     */

   public function index(Request $request)
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
            'Active Financial Year not found.'
        );
    }

    $query = PurchaseInvoice::with(
        'supplier'
    )
    ->where(
        'company_id',
        $companyId
    );

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->latest('id')
    ->get();

    // बाँकी कोड...

if (!$request->filled('status'))
{
    $query->where('status',1);
}
elseif ($request->status == 'active')
{
    $query->where('status',1);
}
elseif ($request->status == 'cancelled')
{
    $query->where('status',0);
}

$startDate = null;
$endDate = null;

if (!$request->filled('financial_year_id'))
{
    if ($activeFy)
    {
        $query->where(
            'financial_year_id',
            $activeFy->id
        );

        $startDate = $activeFy->start_date;
        $endDate = $activeFy->end_date;
    }
}
else
{
    if (
        $request->filled('financial_year_id') &&
        $request->financial_year_id != 'all'
    )
    {
        $query->where(
            'financial_year_id',
            $request->financial_year_id
        );
    }

    $startDate = $request->start_date;
    $endDate   = $request->end_date;
}

    /**
     * 🔥 SUPPLIER FILTER
     */
if (!empty($startDate))
{
    $query->whereDate(
        'purchase_date',
        '>=',
        $startDate
    );
}

if (!empty($endDate))
{
    $query->whereDate(
        'purchase_date',
        '<=',
        $endDate
    );
}


if ($request->supplier_id)
{
    $query->where(
        'supplier_id',
        $request->supplier_id
    );
}

$summaryQuery = clone $query;

$totalRecords =
    $summaryQuery->count();

$totalCancelled =
    (clone $summaryQuery)
    ->where('status',0)
    ->count();

$totalGrandTotal =
    (clone $summaryQuery)
    ->sum('grand_total');

$totalPaidAmount =
    (clone $summaryQuery)
    ->sum('paid_amount');

$totalDueAmount =
    (clone $summaryQuery)
    ->sum('due_amount');



   

    /**
     * 🔥 DATA
     */

    $invoices = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    /**
     * 🔥 SUPPLIERS
     */

    $suppliers = Supplier::where(
        
            'company_id',
            $companyId
        )
        ->get();

    return view(
        'company.purchases.index',
compact(
    'invoices',
    'suppliers',
    'financialYears',
    'startDate',
    'endDate',

    'totalRecords',
    'totalCancelled',
    'totalGrandTotal',
    'totalPaidAmount',
    'totalDueAmount'
)
    );
}
public function create()
{
    $companyId = auth()->user()->company_id;

    $suppliers = Supplier::where('company_id', $companyId)
        ->where('status', 'active')
        ->get();

    $products = Product::where('company_id', $companyId)
        ->get();

    $vats = Vat::where('company_id', $companyId)
        ->where('status', 1)
        ->get();

    $accounts = Account::where('company_id', $companyId)
        ->where('status', 'active')
        ->get();

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )->get();

    // 🔥 ACTIVE FY

    $activeFy = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->where('is_active', 1)
    ->first();

    if (!$activeFy)
    {
        return back()->with(
            'error',
            'Please activate financial year first.'
        );
    }

    // 🔥 INVOICE NUMBER


 $invoiceNo = InvoiceNumberService::generate(
    'PU',
    $companyId,
    $activeFy->id,
    PurchaseInvoice::class
);

    return view(
        'company.purchases.create',
        compact(
            'suppliers',
            'products',
            'vats',
            'invoiceNo',
            'financialYears',
            'accounts'
        )
    );
}
    /**
     * 💾 STORE PURCHASE
     */

    public function store(Request $request)
    {
       $request->validate([
    'supplier_id' => 'required|exists:suppliers,id',
    'purchase_date' =>
     ValidationService::requiredDate(),
    'product_id' => 'required|array',
    'product_id.*' => 'required|exists:products,id',
    'quantity' => 'required|array',
   'quantity.*' => 'required|numeric|min:0.01|max:999999',
    'unit_price' => 'required|array',
   'unit_price.*' => 'required|numeric|min:0|max:999999999',
    'account_id' => 'nullable|exists:accounts,id',
    'paid_amount' =>
ValidationService::amount(),
'discount' =>
ValidationService::amount(),
]);


$companyId = auth()->user()->company_id;

$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    true
)
->first();

if (!$activeFy)
{
    return back()
        ->withInput()
        ->withErrors([
            'financial_year' =>
            'No active financial year found.'
        ]);
}

try
{
       DB::transaction(function () use (
    $request,
    $companyId,
    $activeFy
) {


$purchaseDate = \Carbon\Carbon::parse(
    $request->purchase_date
);

$startDate = \Carbon\Carbon::parse(
    $activeFy->start_date
);

$endDate = \Carbon\Carbon::parse(
    $activeFy->end_date
);

if (
    $purchaseDate->lt($startDate)
    ||
    $purchaseDate->gt($endDate)
)
{
    throw new \Exception(
        'No active financial year found for selected purchase date.'
    );
}

            /**
             * 🔥 CALCULATIONS
             */

            $subtotal = 0;
            $totalVat = 0;
            $grandTotal = 0;

            $items = [];

            foreach (
                $request->product_id as $key => $productId
            ) {

                // 🔥 PRODUCT CHECK

                $product = Product::where(
                        'company_id',
                        $companyId
                    )
                    ->findOrFail($productId);
                    if ($product->status != 'active')
{throw new \Exception(
    $product->name . ' is inactive.'
);
}

                $qty =
                    $request->quantity[$key];

                $price =
                    $request->unit_price[$key];
                    if ($qty <= 0)
{
 throw new \Exception(
    'Quantity must be greater than zero.'
);
}
if ($price < 0)
{
    throw new \Exception(
        'Price cannot be negative.'
    );
}

                $vatRate =
                    $request->vat_rate[$key]
                    ?? 0;

                // 🔥 ITEM SUBTOTAL

                $itemSubtotal =
                    $qty * $price;

                // 🔥 VAT

                $vatAmount =
                    ($itemSubtotal * $vatRate)
                    / 100;

                // 🔥 TOTAL

                $totalPrice =
                    $itemSubtotal + $vatAmount;
if ($totalPrice > 9999999999.99)
{
    throw new \Exception(
        'Amount is too large.'
    );
}

                $subtotal +=
                    $itemSubtotal;

                $totalVat +=
                    $vatAmount;

                $grandTotal +=
                    $totalPrice;

                $items[] = [

                    'product' =>
                        $product,

                    'product_id' =>
                        $productId,

                    'vat_id' =>
                        $request->vat_id[$key]
                        ?? null,

                    'quantity' =>
                        $qty,

                    'unit_price' =>
                        $price,


                    'vat_rate' =>
                        $vatRate,

                    'vat_amount' =>
                        $vatAmount,

                    'total_price' =>
                        $totalPrice,

                ];
            }

         

            $discount =
                $request->discount ?? 0;

            $grandTotal =
                $grandTotal - $discount;

        

            $paidAmount =
                $request->paid_amount ?? 0;

            $dueAmount =
                $grandTotal - $paidAmount;
if ($paidAmount > $grandTotal)
{
    throw new \Exception(
        'Paid amount cannot be greater than total amount.'
    );
}

            // 🔥 PAYMENT STATUS

            if ($dueAmount <= 0)
            {
                $paymentStatus = 'paid';
            }
            elseif ($paidAmount > 0)
            {
                $paymentStatus = 'partial';
            }
            else
            {
                $paymentStatus = 'unpaid';
            }

     if (
    $paidAmount > 0 &&
    $request->account_id
)
{
    $account = Account::where(
        'company_id',
        $companyId
    )->findOrFail(
        $request->account_id
    );
if ($account->current_balance < $paidAmount)
{
    throw new \Exception(
        'Insufficient account balance.'
    );
}
    
} 
            /**
             * 🔥 CREATE INVOICE
             */
$invoiceNo = InvoiceNumberService::generate(
    'PU',
    $companyId,
    $activeFy->id,
    PurchaseInvoice::class
    
);

            $invoice = PurchaseInvoice::create([

                'created_by' =>
                    auth()->id(),

                'company_id' =>
                    $companyId,
                    'financial_year_id' =>

                    $activeFy?->id,

                'supplier_id' =>
                    $request->supplier_id,

               'invoice_no' =>
$invoiceNo,
                    

                'purchase_date' =>
                    $request->purchase_date,
                    

                'subtotal' =>
                    $subtotal,

                'discount' =>
                    $discount,

                'total_vat' =>
                    $totalVat,

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


      
if (
    $paidAmount > 0 &&
    $request->account_id
)
{
    $paymentNo = InvoiceNumberService::generate(
        'PP',
        $companyId,
        $activeFy->id,
        PurchasePayment::class,
        'payment_no'
    );

   
    $payment = PurchasePayment::create([

    'company_id' =>
        $companyId,

    'financial_year_id' =>
        $activeFy->id,

    'purchase_invoice_id' =>
        $invoice->id,

    'supplier_id' =>
        $request->supplier_id,

    'account_id' =>
        $request->account_id,

    'payment_no' =>
        $paymentNo,

    'payment_date' =>
        $request->purchase_date,

    'amount' =>
        $paidAmount,

        'status' => 1,

'note' =>
$request->note
?? 'Purchase Payment',

    'created_by' =>
        auth()->id(),

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

    'balance' =>
        0,

    'created_by' =>
        auth()->id(),

    'status' =>
        1,

]);
// ==========================
// SUPPLIER PAYMENT TRANSACTION
// ==========================

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

   'debit' => $paidAmount,
'credit' => 0,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);

}

         foreach ($items as $item)
            {
                PurchaseItem::create([

                    'company_id' =>
    $companyId,

'financial_year_id' =>
    $activeFy->id,

'created_by' =>
    auth()->id(),

                    'purchase_invoice_id' =>
                        $invoice->id,

                    'product_id' =>
                        $item['product_id'],

                    'vat_id' =>
                        $item['vat_id'],

                    'quantity' =>
                        $item['quantity'],

                    'unit_price' =>
                        $item['unit_price'],

                    'vat_rate' =>
                        $item['vat_rate'],

                    'vat_amount' =>
                        $item['vat_amount'],

                    'price' =>
                      $item['unit_price'],
                      

                      'total' =>
                        $item['total_price'],

                        'total_price' =>
                       $item['total_price'],

                        'status' => 1,
                        

                ]);

             

StockService::increase(

    $item['product'],

    $item['quantity'],

    'purchase',

    $invoice->invoice_no,

    $activeFy->id,

    $invoice->purchase_date,

    $item['unit_price'],

    'Purchase Invoice'

);
            }


///Purchase Invoice Transaction Block
            
SupplierTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'supplier_id'       => $invoice->supplier_id,

    'transaction_date'  => $invoice->purchase_date,

    'voucher_no'        => $invoice->invoice_no,

  
'reference_type' => 'purchase',

    'reference_id'      => $invoice->id,

    'reference_no'      => $invoice->invoice_no,

    'description'       => 'Purchase Invoice',

 'debit' => 0,
'credit' => $invoice->grand_total,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);

        });

         return redirect()
        ->route('company.purchases.index')
        ->with(
            'success',
            'Purchase created successfully.'
        );

}
catch (\Exception $e)
{
    return back()
        ->withInput()
        ->with(
            'error',
            $e->getMessage()
        );
}
            
    }

    /**
     * 👁 SHOW PURCHASE
     */

    public function show($id)
    {
        $invoice = PurchaseInvoice::with([

                'supplier',

                'items.product',

                'company',

            ])
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        return view(
            'company.purchases.show',
            compact('invoice')
        );
    }






public function cancel($id)
{
    DB::beginTransaction();

    try {

        $invoice = PurchaseInvoice::where(
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
                'Purchase Already Cancelled.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET PAYMENTS
        |--------------------------------------------------------------------------
        */

        $payments = PurchasePayment::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'purchase_invoice_id',
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
                    'purchase_payment'
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

                    'purchase_payment_cancel',

                    'Purchase Payment Cancel'

                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK CHECK
        |--------------------------------------------------------------------------
        */

        foreach ($invoice->items as $item)
        {
            if (
                $item->product->current_stock <
                $item->quantity
            )
            {
                throw new \Exception(

                    $item->product->name .
                    ' stock already sold. Purchase cannot be cancelled.'

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
            StockService::decrease(

                $item->product,

                $item->quantity,

                'purchase_cancel',

                $invoice->invoice_no,

                $invoice->financial_year_id,

                $invoice->purchase_date,

                $item->unit_price,

                'Purchase Cancel'

            );
        }

       

        /*
        |--------------------------------------------------------------------------
        | SUPPLIER TRANSACTION DELETE
        |--------------------------------------------------------------------------
        */
        SupplierTransactionService::deleteByReference(

    auth()->user()->company_id,

    'purchase',

    $invoice->id

);

foreach ($payments as $payment)
{
    SupplierTransactionService::deleteByReference(

        auth()->user()->company_id,

        'purchase_payment',

        $payment->id

    );
}

 /*
        |--------------------------------------------------------------------------
        | PAYMENT CANCEL
        |--------------------------------------------------------------------------
        */

        PurchasePayment::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'purchase_invoice_id',
            $invoice->id
        )
        ->update([

            'status' => 0

        ]);
   

        /*
        |--------------------------------------------------------------------------
        | PURCHASE CANCEL
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

            'Purchase Cancelled Successfully'

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



public function print(Request $request)
{
    $companyId =
        auth()->user()->company_id;

    $query = PurchaseInvoice::with(
        'supplier'
    )
    ->where(
        'company_id',
        $companyId
    );

    $activeFy = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->where(
        'is_active',
        1
    )
    ->first();

    $startDate = null;
    $endDate   = null;

    if (!$request->has('financial_year_id'))
    {
        if ($activeFy)
        {
            $query->where(
                'financial_year_id',
                $activeFy->id
            );

            $startDate =
                $activeFy->start_date;

            $endDate =
                $activeFy->end_date;
        }
    }
    else
    {
if (
    $request->filled('financial_year_id') &&
    $request->financial_year_id != 'all'
)
{
    $query->where(
        'financial_year_id',
        $request->financial_year_id
    );
}

$startDate = $request->start_date;
$endDate   = $request->end_date;
     
    }

    /**
     * Supplier Filter
     */

    if ($request->supplier_id)
    {
        $query->where(
            'supplier_id',
            $request->supplier_id
        );
    }

    /**
     * Status Filter
     */

    if (!$request->filled('status'))
    {
        $query->where(
            'status',
            1
        );
    }
    elseif (
        $request->status == 'active'
    )
    {
        $query->where(
            'status',
            1
        );
    }
    elseif (
        $request->status == 'cancelled'
    )
    {
        $query->where(
            'status',
            0
        );
    }

    /**
     * Date Filter
     */

    if (!empty($startDate))
    {
        $query->whereDate(
            'purchase_date',
            '>=',
            $startDate
        );
    }

    if (!empty($endDate))
    {
        $query->whereDate(
            'purchase_date',
            '<=',
            $endDate
        );
    }

    $invoices = $query
        ->orderByDesc(
            'purchase_date'
        )
        ->get();

    return view(
        'company.purchases.print',
        compact(
            'invoices'
        )
    );
}

}