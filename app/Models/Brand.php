<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'updated_by',
        'name',
        'description',
        'image',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
