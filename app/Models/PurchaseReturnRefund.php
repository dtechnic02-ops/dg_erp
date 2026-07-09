<?php

namespace App\Models;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturnRefund extends Model
{protected $fillable = [

'company_id',
 'financial_year_id',

'purchase_return_id',

'account_id',

'refund_no',

'amount',

'payment_method',

'attachment',

'refund_date',

'note',

'created_by',

'status',

];

    /**
     * 🔥 RETURN
     */

    public function purchaseReturn()
    {
        return $this->belongsTo(
            PurchaseReturn::class
        );
    }

    /**
     * 🔥 ACCOUNT
     */

    public function account()
    {
        return $this->belongsTo(
            Account::class
        );
    }
    /**
 * 🔥 REFUNDS
 */

public function financialYear()
{
    return $this->belongsTo(
        FinancialYear::class,
        'financial_year_id'
    );
}
/**
 * 🔥 COMPANY
 */

public function company()
{
    return $this->belongsTo(
        Company::class
    );
}
/**
 * 🔥 CREATED BY
 */

public function createdBy()
{
    return $this->belongsTo(
        User::class,
        'created_by'
    );
}
}