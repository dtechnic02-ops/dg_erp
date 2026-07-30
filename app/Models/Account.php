<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public const GROUP_ASSET = 'Asset';

    public const GROUP_LIABILITY = 'Liability';

    public const GROUP_INCOME = 'Income';

    public const GROUP_EXPENSE = 'Expense';

    public const GROUP_EQUITY = 'Equity';

    public const SUB_LEDGER_CUSTOMER = 'customer';

    public const SUB_LEDGER_SUPPLIER = 'supplier';

    public const SUB_LEDGER_EMPLOYEE = 'employee';

    public const SUB_LEDGER_PARTY = 'party';

    protected $fillable = [

        'company_id',

        'account_group',

        'account_type',

        'sub_ledger_type',

        'bank_name',

        'account_name',

        'branch',

        'account_no',

        'iban',

        'swift_code',

        'currency',

        'opening_balance',

        'current_balance',

        'image_path',

        'note',

        'status',
    ];

    public function requiresSubLedger(): bool
    {
        return !empty($this->sub_ledger_type);
    }

    public static function subLedgerTypeLabels(): array
    {
        return [
            self::SUB_LEDGER_CUSTOMER => 'Customer',
            self::SUB_LEDGER_SUPPLIER => 'Supplier',
            self::SUB_LEDGER_EMPLOYEE => 'Employee',
            self::SUB_LEDGER_PARTY    => 'Party',
        ];
    }

    public static function accountGroupLabels(): array
    {
        return [
            self::GROUP_ASSET     => 'Asset',
            self::GROUP_LIABILITY => 'Liability',
            self::GROUP_INCOME    => 'Income',
            self::GROUP_EXPENSE   => 'Expense',
            self::GROUP_EQUITY    => 'Equity',
        ];
    }

    public static function accountTypeLabels(): array
    {
        return [
            'Cash'   => 'Cash',
            'Bank'   => 'Bank',
            'ATM'    => 'ATM',
            'Wallet' => 'Wallet',
            'Other'  => 'Other',
        ];
    }


    /**
     * COMPANY
     */
    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }


    /**
     * PAYMENTS
     */
    public function payments()
    {
        return $this->hasMany(
            InvoicePayment::class
        );
    }
    /**
 * SALES RETURN REFUNDS
 */

public function salesReturnRefunds()
{
    return $this->hasMany(
        SalesReturnRefund::class
    );
}
/**
 * SALES PAYMENTS
 */

public function salesPayments()
{
    return $this->hasMany(
        SalesPayment::class
    );
}
public function journalItems()
{

return $this->hasMany(

JournalItem::class

);

}
public function transactions()
{
    return $this->hasMany(
        AccountTransaction::class,
        'account_id'
    );
}

}
