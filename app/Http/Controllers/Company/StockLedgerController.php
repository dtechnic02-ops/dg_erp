<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\FinancialYear;
use Barryvdh\DomPDF\Facade\Pdf;

class StockLedgerController extends Controller
{
    public function index(Request $request)
{
    $companyId = auth()->user()->company_id;

    /*
    |--------------------------------------------------------------------------
    | ACTIVE FINANCIAL YEAR
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | DROPDOWNS
    |--------------------------------------------------------------------------
    */

    $products = Product::where(
        'company_id',
        $companyId
    )
    ->latest()
    ->get();

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->orderByDesc(
        'start_date'
    )
    ->get();

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

$financialYearId =
    $request->financial_year_id
    ?? $activeFy?->id;

if ($financialYearId == 'all')
{
    $startDate =
        $request->start_date;

    $endDate =
        $request->end_date;
}
else
{
    $startDate =
        $request->start_date
        ?? $activeFy?->start_date;

    $endDate =
        $request->end_date
        ?? $activeFy?->end_date;
}

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    $query = StockMovement::with(
        'product'
    )
    ->where(
        'company_id',
        $companyId
    );

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL YEAR FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $financialYearId &&
        $financialYearId != 'all'
    )
    {
        $query->where(
            'financial_year_id',
            $financialYearId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT FILTER
    |--------------------------------------------------------------------------
    */

    if ($request->filled('product_id'))
    {
        $query->where(
            'product_id',
            $request->product_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TYPE FILTER
    |--------------------------------------------------------------------------
    */

    if ($request->filled('type'))
    {
        switch ($request->type)
        {
            case 'return':

                $query->whereIn(
                    'type',
                    [
                        'sale_return',
                        'purchase_return'
                    ]
                );

            break;

            case 'in':

                $query->whereIn(
                    'type',
                    [
                        'purchase',
                        'sale_return',
                        'purchase_return',
                        'opening_stock',
                        'adjustment_in'
                    ]
                );

            break;

            case 'out':

                $query->whereIn(
                    'type',
                    [
                        'sale',
                        'adjustment_out'
                    ]
                );

            break;

            default:

                $query->where(
                    'type',
                    $request->type
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

    if ($startDate)
    {
        $query->whereDate(
            'transaction_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'transaction_date',
            '<=',
            $endDate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOVEMENTS
    |--------------------------------------------------------------------------
    */

    $movements = $query
        ->latest()
        ->paginate(50)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    $summary = [

        'total_in' => $query->clone()
            ->where(
                'quantity',
                '>',
                0
            )
            ->sum(
                'quantity'
            ),

        'total_out' => abs(
            $query->clone()
                ->where(
                    'quantity',
                    '<',
                    0
                )
                ->sum(
                    'quantity'
                )
        ),

        'total_movements' => $query->clone()
            ->count(),

    ];

    /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

    return view(
        'company.stock-ledger.index',
        compact(

            'movements',

            'products',

            'summary',

            'financialYears',

            'financialYearId',

            'startDate',

            'endDate'

        )
    );
}

    /**
     * 🔄 STOCK SYNC
     */

    public function sync()
    {
        $companyId = auth()->user()->company_id;

        $products = Product::where(
            'company_id',
            $companyId
        )->get();

        foreach ($products as $product)
        {
            $stock = StockMovement::where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'product_id',
                    $product->id
                )
                ->sum('quantity');

            $product->update([

                'current_stock' => $stock

            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                'Stock synced successfully.'
            );
    }

    /**
     * 📄 PDF EXPORT
     */

   public function pdf(Request $request)
{
    $companyId = auth()->user()->company_id;

    /*
    |--------------------------------------------------------------------------
    | ACTIVE FINANCIAL YEAR
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | DROPDOWNS
    |--------------------------------------------------------------------------
    */

    $products = Product::where(
        'company_id',
        $companyId
    )
    ->latest()
    ->get();

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->orderByDesc(
        'start_date'
    )
    ->get();

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    $financialYearId =
        $request->financial_year_id
        ?? $activeFy?->id;

    if ($financialYearId == 'all')
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
    }
    else
    {
        $startDate =
            $request->start_date
            ?? $activeFy?->start_date;

        $endDate =
            $request->end_date
            ?? $activeFy?->end_date;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    $query = StockMovement::with(
        'product'
    )
    ->where(
        'company_id',
        $companyId
    );

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL YEAR
    |--------------------------------------------------------------------------
    */

    if (
        $financialYearId &&
        $financialYearId != 'all'
    )
    {
        $query->where(
            'financial_year_id',
            $financialYearId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT FILTER
    |--------------------------------------------------------------------------
    */

    if ($request->filled('product_id'))
    {
        $query->where(
            'product_id',
            $request->product_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TYPE FILTER
    |--------------------------------------------------------------------------
    */

    if ($request->filled('type'))
    {
        switch ($request->type)
        {
            case 'return':

                $query->whereIn(
                    'type',
                    [
                        'sale_return',
                        'purchase_return'
                    ]
                );

            break;

            case 'in':

                $query->whereIn(
                    'type',
                    [
                        'purchase',
                        'sale_return',
                        'purchase_return',
                        'opening_stock',
                        'adjustment_in'
                    ]
                );

            break;

            case 'out':

                $query->whereIn(
                    'type',
                    [
                        'sale',
                        'adjustment_out'
                    ]
                );

            break;

            default:

                $query->where(
                    'type',
                    $request->type
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

    if ($startDate)
    {
        $query->whereDate(
            'transaction_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'transaction_date',
            '<=',
            $endDate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */

    $movements = $query
        ->orderBy(
            'transaction_date'
        )
        ->orderBy(
            'id'
        )
        ->get();

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    $summary = [

        'total_in' => $query->clone()
            ->where(
                'quantity',
                '>',
                0
            )
            ->sum(
                'quantity'
            ),

        'total_out' => abs(
            $query->clone()
                ->where(
                    'quantity',
                    '<',
                    0
                )
                ->sum(
                    'quantity'
                )
        ),

        'total_movements' => $query->clone()
            ->count(),

    ];

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    $pdf = Pdf::loadView(
        'company.stock-ledger.pdf',
        compact(
            'movements',
            'products',
            'summary',
            'financialYears',
            'financialYearId',
            'startDate',
            'endDate'
        )
    );

    return $pdf->download(
        'stock-ledger.pdf'
    );
}
}