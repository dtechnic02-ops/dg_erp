<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnRefund extends Model
{
    protected $fillable = [

        'company_id',
        'financial_year_id',
        'sales_return_id',

        'customer_id',

        'account_id',

        'refund_no',

        'refund_date',

        'refund_amount',

        'note',

        'created_by',

        'status',

    ];

    /**
     * SALES RETURN
     */

    public function salesReturn()
    {
        return $this->belongsTo(
            SalesReturn::class
        );
    }

    /**
     * CUSTOMER
     */

    public function customer()
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    /**
     * ACCOUNT
     */

    public function account()
    {
        return $this->belongsTo(
            Account::class
        );
    }
    public function financialYear()
{
    return $this->belongsTo(
        FinancialYear::class
    );
}
}