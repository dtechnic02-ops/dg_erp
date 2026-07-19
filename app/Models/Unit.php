<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // 🔥 ADD THIS

class Unit extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'short_name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
