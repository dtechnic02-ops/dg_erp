<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'amount',
        'method',
        'screenshot',
        'status',
        'note'
    ];

    // 🔥 RELATIONS
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }
}