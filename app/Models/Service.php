<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'service_category_id',
        'name',
        'service_code',
        'slug',
        'price',
        'vat_id',
        'upload_path',
        'description',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(
            ServiceCategory::class,
            'service_category_id'
        );
    }

    public function vat()
    {
        return $this->belongsTo(
            Vat::class,
            'vat_id'
        );
    }

    public function salesItems()
    {
        return $this->hasMany(
            SalesItem::class,
            'service_id'
        );
    }
}
