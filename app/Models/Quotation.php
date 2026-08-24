<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'company_id', 'financial_year_id', 'customer_id', 'quotation_no', 'quotation_date',
        'valid_until', 'reference_no', 'note', 'subtotal', 'discount', 'total_vat',
        'grand_total', 'status', 'created_by', 'approved_by', 'approved_at',
        'converted_by', 'converted_at', 'sales_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date', 'valid_until' => 'date',
            'subtotal' => 'decimal:4', 'discount' => 'decimal:4',
            'total_vat' => 'decimal:4', 'grand_total' => 'decimal:4',
            'approved_at' => 'datetime', 'converted_at' => 'datetime',
        ];
    }

    public function items() { return $this->hasMany(QuotationItem::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function financialYear() { return $this->belongsTo(FinancialYear::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function converter() { return $this->belongsTo(User::class, 'converted_by'); }
}
