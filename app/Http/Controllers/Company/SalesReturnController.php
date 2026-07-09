<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\FinancialYear;
use App\Services\InvoiceNumberService;

use App\Services\StockService;


class SalesReturnController extends Controller
{
    /**
     * 🔥 INDEX
     */

    public function index(Request $request)
    {
        $companyId =
            auth()->user()->company_id;

        $query = SalesReturn::with([

        
                   'customer',
               'invoice',
              'refunds'

             ])
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

$activeFy = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->where(
        'is_active',
        1
    )
    ->first();

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


        /**
         * 🔥 FILTERS
         */

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

        if($request->customer_id)
        {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        $returns = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /**
         * 🔥 TOTALS
         */

        $grandTotal =
            $query->sum(
                'grand_total'
            );

        $customers = Customer::where(
                'company_id',
                $companyId
            )
            ->get();

        return view(
            'company.sales-return.index',
 compact(
    'returns',
    'customers',
    'grandTotal',
    'financialYears',
    'startDate',
    'endDate'
)
        );
    }

    /**
     * 🔥 CREATE PAGE
     */

    public function create($id)
    {
        $companyId =
            auth()->user()->company_id;

        $invoice = SalesInvoice::with([

                'customer',
                'items.product',
                'items.service',

            ])
            ->where(
                'company_id',
                $companyId
            )
            ->findOrFail($id);

        /**
         * 🔥 RETURN NUMBER
         */
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



$returnNo = InvoiceNumberService::generate(

    'SR',

    $companyId,

    $activeFy->id,

    SalesReturn::class,

    'return_no'

);


        return view(
            'company.sales-return.create',
            compact(
                'invoice',
                'returnNo'
            )
        );
    }

    /**
     * 🔥 STORE RETURN
     */

    public function store(Request $request)
    {
        $request->validate([

            'sales_invoice_id' =>
                'required',

            'customer_id' =>
                'required',

            'return_date' =>
                'required|date',

            'sales_item_id' =>
                'required|array',

            'quantity' =>
                'required|array',
                'return_no' =>
            'required',

        ]);
        
           try
        {
        $return = DB::transaction(function ()
use ($request) {

            /**
             * 🔥 VARIABLES
             */

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
    throw new \Exception(
        'Financial Year not found for selected return date.'
    );
}

$invoice = SalesInvoice::with(
    'items'
)
->where(
    'company_id',
    $companyId
)
->findOrFail(
    $request->sales_invoice_id
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
        'Sales Invoice belongs to another Financial Year.'
    );
}

            $totalSubtotal = 0;

            $totalVat = 0;

            $grandTotal = 0;

            $hasReturn = false;

            /**
             * 🔥 DAMAGE PHOTO
             */

            $photo = null;

            if (
                $request->hasFile(
                    'damage_photo'
                )
            ) {

                $photo = $request
                    ->file('damage_photo')
                    ->store(
                        "companies/{$companyId}/returns",
                        'public'
                    );
            }

            /**
             * 🔥 CREATE RETURN
             */

            $return = SalesReturn::create([

                'company_id' =>
                    $companyId,

                    'financial_year_id' =>
                    $activeFy->id,

                'sales_invoice_id' =>
                    $request->sales_invoice_id,

                'customer_id' =>
                    $request->customer_id,

                'return_no' =>
                    $request->return_no,

                'return_date' =>
                    $request->return_date,

                'subtotal' => 0,

                'total_vat' => 0,

                'grand_total' => 0,

                'note' =>
                    $request->note,

                'damage_photo' =>
                    $photo,

                'created_by' =>
                    auth()->id(),

                'status' => 1,

            ]);

            /**
             * 🔥 ITEMS LOOP
             */

            foreach (
                $request->sales_item_id
                as $key => $salesItemId
            ) {

                $returnQty =
                    (float)
                    ($request->quantity[$key] ?? 0);

                /**
                 * 🔥 SKIP EMPTY
                 */

                if (
                    $returnQty <= 0
                ) {
                    continue;
                }

                $hasReturn = true;

                /**
                 * 🔥 SALES ITEM
                 */

                $salesItem = SalesItem::where(
                        'company_id',
                        $companyId
                    )
                    ->findOrFail(
                        $salesItemId
                    );

                /**
                 * 🔥 BLOCK SERVICE
                 */

                if (
                    !$salesItem->product_id
                ) {
                    continue;
                }

                /**
                 * 🔥 AVAILABLE QTY
                 */

           $alreadyReturned =
    SalesReturnItem::where(
        'company_id',
        $companyId
    )
    ->where(
        'sales_item_id',
        $salesItem->id
    )
    ->whereHas(
        'salesReturn',
        function ($q) use ($request) {

            $q->where(
                'sales_invoice_id',
                $request->sales_invoice_id
            )
            ->where(
                'status',
                1
            );

        }
    )
    ->sum(
        'quantity'
    );

$availableQty =
    $salesItem->quantity
    - $alreadyReturned;

if (
    $returnQty >
    $availableQty
)
{
    throw new \Exception(

    'Return qty exceeds available qty.'

);
}

                /**
                 * 🔥 PRODUCT
                 */

                $product = Product::where(
                        'company_id',
                        $companyId
                    )
                    ->findOrFail(
                        $salesItem->product_id
                    );

                /**
                 * 🔥 SUBTOTAL
                 */

                $subtotal =

                    $returnQty *

                    $salesItem->unit_price;

                /**
                 * 🔥 ADD SUBTOTAL
                 */

                $totalSubtotal +=
                    $subtotal;

                /**
                 * 🔥 VAT
                 */

                $vatAmount = round(

                    ($subtotal *

                    $salesItem->vat_rate)

                    / 100,

                    2

                );

                /**
                 * 🔥 ADD VAT
                 */

                $totalVat +=
                    $vatAmount;

                /**
                 * 🔥 TOTAL
                 */

                $total =

                    $subtotal +

                    $vatAmount;

                /**
                 * 🔥 ADD GRAND TOTAL
                 */

                $grandTotal +=
                    $total;

                /**
                 * 🔥 SAVE RETURN ITEM
                 */

SalesReturnItem::create([
'company_id' =>
    $companyId,

'financial_year_id' =>
    $activeFy->id,

'sales_return_id' =>
    $return->id,

'sales_item_id' =>
    $salesItem->id,

'product_id' =>
    $product->id,

'quantity' =>
    $returnQty,

'unit_price' =>
    $salesItem->unit_price,

'vat_rate' =>
    $salesItem->vat_rate,

'vat_amount' =>
    $vatAmount,

'total_price' =>
    $total,

'created_by' =>
    auth()->id(),

'status' =>
    1,

 ]);

                /**
                 * 🔥 STOCK RETURN
                 */

                StockService::increase(

    $product,

    $returnQty,

    'sales_return',

    $return->return_no,

    $activeFy->id,

    $return->return_date,

    $salesItem->unit_price,

    'Sales Return'

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
            /*

            /**
             * 🔥 UPDATE RETURN TOTALS
             */
$return->update([

    'subtotal'      => $totalSubtotal,

    'total_vat'     => $totalVat,

    'grand_total'   => $grandTotal,

    'adjust_amount' => 0,

    'refund_amount' => $grandTotal,

]);




  return $return;


        });

        return redirect()
            ->route(
                'company.sales-return.show',
                $return->id
            )
            ->with(
                'success',
                'Sales return saved successfully.'
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


 public function show($id)
{
    $companyId =
        auth()->user()->company_id;

    $return = SalesReturn::with([

        'customer',
        'invoice',
        'items',
        'items.product',
        'refunds'

    ])
    ->where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    return view(
        'company.sales-return.show',
        compact(
            'return'
        )
    );
}
    }