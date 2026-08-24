<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'company_id', 'item_type', 'product_id', 'service_id',
        'description', 'quantity', 'unit_price', 'vat_rate', 'vat_amount', 'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4', 'unit_price' => 'decimal:4',
            'vat_rate' => 'decimal:4', 'vat_amount' => 'decimal:4', 'total_price' => 'decimal:4',
        ];
    }

    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
