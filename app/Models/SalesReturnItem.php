<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Service;

class SalesReturnItem extends Model
{

protected $fillable = [

    'company_id',

    'financial_year_id',

    'sales_return_id',

    'sales_item_id',

    'product_id',

    'service_id',

    'quantity',

    'unit_price',

    'vat_rate',

    'vat_amount',

    'total_price',

    'created_by',

    'status',

];

public function product()
{

return $this->belongsTo(

Product::class,

'product_id'

);

}

public function service()
{
    return $this->belongsTo(
        Service::class,
        'service_id'
    );
}

public function salesItem()
{
    return $this->belongsTo(

        SalesItem::class,

        'sales_item_id'

    );
}
public function salesReturn()
{
    return $this->belongsTo(
        SalesReturn::class
    );
}


}

