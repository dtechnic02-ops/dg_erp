<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
protected $fillable = [
    'name',
    'user_limit',
    'price',
    'customer_limit',
    'duration_days',
    'type',
];
}