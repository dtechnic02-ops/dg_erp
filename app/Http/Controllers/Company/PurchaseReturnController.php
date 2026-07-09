<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationService;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\FinancialYear;
use App\Services\StockService;
use App\Services\InvoiceNumberService;
use App\Models\SupplierTransaction;
use App\Services\SupplierTransactionService;

class PurchaseReturnController extends Controller
{


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

$query = PurchaseReturn::with([
    'supplier',
    'purchaseInvoice',
    'refunds'
])
->where(
    'company_id',
    $companyId
);
  
if ($request->supplier_id)
{
    $query->where(
        'supplier_id',
        $request->supplier_id
    );
}
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
// all => कुनै filter छैन

   $startDate = null;
$endDate = null;

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

   if ($request->filled('start_date'))
{
    $startDate = $request->start_date;
}

if ($request->filled('end_date'))
{
    $endDate = $request->end_date;
}
}

if (!empty($startDate))
{
    $query->whereDate(
        'return_date',
        '>=',
        $startDate
    );
}

if (!empty($endDate))
{
    $query->whereDate(
        'return_date',
        '<=',
        $endDate
    );
}
$summaryQuery = clone $query;

$totalRecords =
    $summaryQuery->count();

$totalCancelled =
    (clone $query)
    ->where('status',0)
    ->count();


$totalSubtotal =
    $summaryQuery->sum(
        'subtotal'
    );

$totalVat =
    $summaryQuery->sum(
        'total_vat'
    );

$totalGrandTotal =
    $summaryQuery->sum(
        'grand_total'
    );

    $returns = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    $suppliers = Supplier::where(
            'company_id',
            $companyId
        )
        ->get();
        $financialYears = FinancialYear::where(
    'company_id',
    $companyId
)->get();

return view(
    'company.purchase-return.index',
    compact(
        'returns',
        'suppliers',
        'financialYears',
        'startDate',
        'endDate',
        'totalRecords',
        'totalCancelled',
        'totalSubtotal',
        'totalVat',
        'totalGrandTotal'
    )
);
}
    /**
     * 🔥 CREATE PAGE
     */

    public function create($id)
    {
        $invoice = PurchaseInvoice::with([

                'supplier',

                'items.product',

                'company'

            ])


    
            
            ->where(
                'company_id',
                auth()->user()->company_id
                
            )
            
            ->findOrFail($id);

        if ($invoice->status == 0)
{
    return back()->with(
        'error',
        'Cancelled purchase cannot be returned.'
    );
}

return view(
    'company.purchase-return.create',
    compact('invoice')
);
        
    }

    /**
     * 🔥 STORE PURCHASE RETURN
     */

    public function store(Request $request)
    {
       $request->validate([

'purchase_invoice_id' =>

'required|exists:purchase_invoices,id',

'return_date' =>
ValidationService::requiredDate(),

'purchase_item_id' => 'required|array',

'purchase_item_id.*' =>

'required|exists:purchase_items,id',

'product_id' => 'required|array',

'product_id.*' =>

'required|exists:products,id',

'quantity' =>
'required|array',

'quantity.*' =>
'required|numeric|min:0.01|max:999999',

'unit_price' =>
'required|array',

'unit_price.*' =>
'required|numeric|min:0|max:999999999',


]);
try
{
$purchaseReturn = null;
        DB::transaction(function () use ($request, &$purchaseReturn) {

            $companyId =
                auth()->user()->company_id;

$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->whereDate(
    'start_date',
    '<=',
    $request->return_date
)
->whereDate(
    'end_date',
    '>=',
    $request->return_date
)
->first();
if (!$activeFy)
{
    throw new Exception(
        'Financial Year not found for selected return date.'
    );
}
            $invoice = PurchaseInvoice::with(
                    'items'
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->findOrFail(
                    $request->purchase_invoice_id
                );


/*
|--------------------------------------------------------------------------
| FINANCIAL YEAR CHECK
|--------------------------------------------------------------------------
*/

if (
    $invoice->financial_year_id !=
    $activeFy->id
)
{
    throw new \Exception(
        'Purchase Invoice belongs to another Financial Year.'
    );
}
                

            /**
             * 🔥 RETURN NUMBER
             */

      $returnNo = InvoiceNumberService::generate(
    'PR',
    $companyId,
    $activeFy->id,
    PurchaseReturn::class,
    'return_no'
);
    
if ($invoice->status == 0)
{
    throw new \Exception(
        'Cancelled purchase cannot be returned.'
    );
}

            /**
             * 🔥 TOTALS
             */

    
            $subtotal = 0;

            $totalVat = 0;

            $grandTotal = 0;

            $hasReturn = false;

            /**
             * 🔥 CREATE RETURN
             */

            $purchaseReturn =
                PurchaseReturn::create([

                    'created_by' =>
                        auth()->id(),

                    'company_id' =>
                        $companyId,
'financial_year_id' =>
    $activeFy->id,
                    'purchase_invoice_id' =>
                        $invoice->id,

                    'supplier_id' =>
                        $invoice->supplier_id,

                    'return_no' =>
                        $returnNo,

                    'return_date' =>
                        $request->return_date,

                    'subtotal' => 0,

                    'total_vat' => 0,

                    'grand_total' => 0,
                    'refund_amount' => 0,

                    'adjust_amount' => 0,

                    'note' =>
                        $request->note,

                    'status' => 1,

                ]);


            /**
             * 🔥 RETURN ITEMS
             */

            foreach (
                $request->product_id as $key => $productId
            ) {

                $qty =
                    (float)
                    ($request->quantity[$key] ?? 0);

                // SKIP EMPTY

                if ($qty <= 0)
                {
                    continue;
                }

                $hasReturn = true;

                /**
                 * 🔥 PRODUCT
                 */

                $product = Product::where(
                        'company_id',
                        $companyId
                    )
                    ->findOrFail($productId);
                    if ($product->status != 'active')
{
    throw new \Exception(
        $product->name . ' is inactive.'
    );
}

                /**
                 * 🔥 PURCHASE ITEM
                 */

              $purchaseItem = PurchaseItem::where(

    'company_id',

    $companyId

)
->where(

    'purchase_invoice_id',

    $invoice->id

)
->findOrFail(

    $request->purchase_item_id[$key]

);

if (

    $purchaseItem->product_id !=

    $productId

)
{
    throw new \Exception(

        'Purchase item mismatch.'

    );
}

                /**
                 * 🔥 ALREADY RETURNED
                 */

                $alreadyReturned =
                    PurchaseReturnItem::where(
                        'company_id',
                        $companyId
                    )
                   ->where(
    'purchase_item_id',
    $purchaseItem->id
)
                   ->whereHas(
    'purchaseReturn',
    function ($q) use ($invoice) {

        $q->where(
            'purchase_invoice_id',
            $invoice->id
        )
        ->where(
            'status',
            1
        );

    }
)
->sum('quantity');




                /**
                 * 🔥 REMAINING QTY
                 */

                $remainingQty =
                    $purchaseItem->quantity
                    - $alreadyReturned;

                /**
                 * 🔥 RETURN LIMIT
                 */

                if ($qty > $remainingQty)
                {
                    throw new \Exception(

                        $product->name .

                        ' return qty exceeds available qty.'

                    );
                }

                /**
                 * 🔥 STOCK CHECK
                 */

                if (
                    $product->current_stock < $qty
                )
                {
                    throw new \Exception(

                        $product->name .

                        ' stock not enough.'

                    );
                }

                /**
                 * 🔥 CALCULATION
                 */

                $price =
                    (float)
                    ($request->unit_price[$key] ?? 0);

                $vatRate =
                    (float)
                    ($request->vat_rate[$key] ?? 0);

                $lineSubtotal =
                    $qty * $price;

                $vatAmount =
                    ($lineSubtotal * $vatRate)
                    / 100;

                $totalPrice =
                    $lineSubtotal
                    + $vatAmount;

                /**
                 * 🔥 TOTALS
                 */

                $subtotal +=
                    $lineSubtotal;

                $totalVat +=
                    $vatAmount;

                $grandTotal +=
                    $totalPrice;

                /**
                 * 🔥 CREATE RETURN ITEM
                 */

                PurchaseReturnItem::create([

    'created_by' => auth()->id(),

    'company_id' => $companyId,

    'financial_year_id' => $activeFy->id,

    'purchase_return_id' => $purchaseReturn->id,
    'purchase_item_id' => $purchaseItem->id,

    'product_id' => $productId,

    'quantity' => $qty,

    'unit_price' => $price,

    'vat_rate' => $vatRate,

    'vat_amount' => $vatAmount,

    'total_price' => $totalPrice,

    'status' => 1,

]);
                /**
                 * 🔥 STOCK DECREASE
                 */

StockService::decrease(

$product,

$qty,

'purchase_return',

$purchaseReturn->return_no,

$activeFy->id,

$purchaseReturn->return_date,

$price,

'Purchase Return'

);
            }

            /**
             * 🔥 EMPTY RETURN BLOCK
             */

            if (!$hasReturn)
            {
                throw new \Exception(
                    'Please enter return qty.'
                );
            }

            /**
             * 🔥 UPDATE RETURN TOTALS
             */

            $purchaseReturn->update([

                'subtotal' =>
                    $subtotal,

                'total_vat' =>
                    $totalVat,

                'grand_total' =>
                    $grandTotal,

            ]);
$supplier = Supplier::where(
    'company_id',
    $companyId
)
->findOrFail(
    $invoice->supplier_id
);



$currentDue = max(
    0,
    abs($supplier->current_balance)
);

$adjustAmount = min(
    $currentDue,
    $grandTotal
);

$refundAmount =
    $grandTotal -
    $adjustAmount;

    $purchaseReturn->update([

    'refund_amount' => $refundAmount,

    'adjust_amount' => $adjustAmount,

]);
// ==========================
// PURCHASE RETURN TRANSACTION
// ==========================


SupplierTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'supplier_id'       => $invoice->supplier_id,

    'transaction_date'  => $purchaseReturn->return_date,

    'voucher_no'        => $purchaseReturn->return_no,

    'reference_type'    => 'purchase_return',

    'reference_id'      => $purchaseReturn->id,

    'reference_no'      => $purchaseReturn->return_no,

    'description'       => 'Purchase Return',

    'debit'             => $purchaseReturn->grand_total,

    'credit'            => 0,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);


       
          

            

        });

       return redirect()
    ->route(
        'company.purchase-return.show',
        $purchaseReturn->id
    )
    ->with(
        'success',
        'Purchase return created successfully.'
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
            * 🔥 SHOW PURCHASE RETURN
      */

            public function show($id)
       {
         $return = PurchaseReturn::with([

            'supplier',

            'purchaseInvoice',

            'items.product',

            'refunds.account'

        ])
        ->where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

          return view(
        'company.purchase-return.show',
        compact('return')
    );
}
public function print(Request $request)
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

    $query = PurchaseReturn::with([
        'supplier',
        'purchaseInvoice',
        'refunds'
    ])
    ->where(
        'company_id',
        $companyId
    );

    if ($request->supplier_id)
    {
        $query->where(
            'supplier_id',
            $request->supplier_id
        );
    }

    $startDate = null;
$endDate = null;

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

   if ($request->filled('start_date'))
{
    $startDate = $request->start_date;
}

if ($request->filled('end_date'))
{
    $endDate = $request->end_date;
}
}
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
// all => कुनै filter छैन


    if (!empty($startDate))
    {
        $query->whereDate(
            'return_date',
            '>=',
            $startDate
        );
    }

    if (!empty($endDate))
    {
        $query->whereDate(
            'return_date',
            '<=',
            $endDate
        );
    }

    $returns = $query
        ->latest()
        ->get();

    return view(
        'company.purchase-return.print-list',
        compact('returns')
    );
}



public function cancel($id)
{
   
    DB::beginTransaction();

    try {
        

$return = PurchaseReturn::with(
    'supplier',
    'purchaseInvoice',
    'items.product',
    'refunds.account'
)
->where(
    'company_id',
    auth()->user()->company_id
)
->findOrFail($id);
if ($return->status == 0)
{
    return back()->with(
        'error',
        'Return already cancelled.'
    );
}
$activeRefundAmount =
    $return->refunds()
        ->where(
            'status',
            1
        )
        ->sum(
            'amount'
        );

if ($activeRefundAmount > 0)
{
    throw new \Exception(
        'Refund exists. Cancel refund first.'
    );
}

        /**
         * 🔥 PURCHASE INVOICE
         */

        $invoice = $return->purchaseInvoice;

if (!$invoice)
{
    throw new \Exception(
        'Purchase invoice not found.'
    );
}

        /**
         * 🔥 STOCK RESTORE
         */

        foreach ($return->items as $item)
        {
StockService::increase(

$item->product,

$item->quantity,

'purchase_return_cancel',

$return->return_no,

$return->financial_year_id,

$return->return_date,

$item->unit_price,

'Purchase Return Cancel'

);
        }

// ==========================
// PURCHASE RETURN TRANSACTION DELETE
// ==========================
$supplierTransaction = SupplierTransaction::where(
    'company_id',
    auth()->user()->company_id
)
->where(
    'reference_type',
    'purchase_return'
)
->where(
    'reference_id',
    $return->id
)
->where(
    'status',
    1
)
->firstOrFail();

SupplierTransactionService::reverseTransaction(

    $supplierTransaction,

    'purchase_return_cancel',

    'Purchase Return Cancel'

);

$return->update([

    'status' => 0,

    'note' =>
        trim(
            ($return->note ?? '')
            .
            ' [Cancelled]'
        ),

]);



        DB::commit();

        return back()->with(
            'success',
            'Purchase return cancelled successfully.'
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
}