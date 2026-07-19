<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = [

        'company_id',

        'name',

        'description',

        'status'

    ];


    public const STATUS_ACTIVE='active';


    public function scopeCompany($query)
    {
        return $query->where(
            'company_id',
            auth()->user()->company_id
        );
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

