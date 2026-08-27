<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProductsExport implements FromCollection
{
    protected $companyId;

    protected $search;

    protected $stockFilter;

    protected $brandId;

    protected $categoryId;

    public function __construct(
        $companyId,
        $search = null,
        $stockFilter = null,
        $brandId = null,
        $categoryId = null
    ) {

        $this->companyId =
            $companyId;

        $this->search =
            $search;

        $this->stockFilter =
            $stockFilter;

        $this->brandId =
            $brandId;

        $this->categoryId =
            $categoryId;
    }

    public function collection()
{

$query = Product::

where(
'company_id',
$this->companyId
)

->where(
'status',
'!=',
'inactive'
);

 // SEARCH

if ($this->search)
{
$query->where(function ($q) {

$q->where(
'name',
'like',
'%' . $this->search . '%'
)

->orWhere(
'barcode',
'like',
'%' . $this->search . '%'
);

});
}

        // STOCK FILTER

        if ($this->stockFilter == 'out')
        {
            $query->where(
                'current_stock',
                '<=',
                0
            );
        }

        elseif ($this->stockFilter == 'low')
        {
            $query->whereColumn(
                'current_stock',
                '<=',
                'stock_alert'
            )
            ->where(
                'current_stock',
                '>',
                0
            );
        }

        elseif ($this->stockFilter == 'available')
        {
            $query->where(
                'current_stock',
                '>',
                0
            );
        }

        // BRAND FILTER

        if ($this->brandId)
        {
            $query->where(
                'brand_id',
                $this->brandId
            );
        }

        if ($this->categoryId)
        {
            $query->where(
                'category_id',
                $this->categoryId
            );
        }

        return $query
            ->latest()
            ->get();
    }
}
