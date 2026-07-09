<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'type',
        'is_default',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}