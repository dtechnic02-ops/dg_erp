<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'upload_path',
        'status',
        'created_by',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'service_category_id');
    }
}
