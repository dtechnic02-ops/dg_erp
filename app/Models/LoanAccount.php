<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class LoanAccount extends Model

{

    public const STATUS_CANCELLED = 0;



    public const STATUS_ACTIVE = 1;



    protected $fillable = [

        'company_id',

        'financial_year_id',

        'loan_no',

        'loan_name',

        'loan_type',

        'party_account_id',

        'account_id',

        'principal_amount',

        'interest_rate',

        'remaining_principal',

        'start_date',

        'end_date',

        'next_payment_date',

        'attachment',

        'note',

        'created_by',

        'updated_by',

        'cancelled_by',

        'cancelled_at',

        'status',

    ];



    protected $casts = [

        'principal_amount'     => 'decimal:2',

        'interest_rate'        => 'decimal:2',

        'remaining_principal'  => 'decimal:2',

        'start_date'           => 'date',

        'end_date'             => 'date',

        'next_payment_date'    => 'date',

        'cancelled_at'         => 'datetime',

        'status'               => 'integer',

    ];



    public function partyAccount()

    {

        return $this->belongsTo(PartyAccount::class, 'party_account_id');

    }



    public function account()

    {

        return $this->belongsTo(Account::class);

    }



    public function payments()

    {

        return $this->hasMany(LoanPayment::class);

    }



    public function company()

    {

        return $this->belongsTo(Company::class);

    }



    public function financialYear()

    {

        return $this->belongsTo(FinancialYear::class);

    }



    public function createdBy()

    {

        return $this->belongsTo(User::class, 'created_by');

    }



    public function updatedBy()

    {

        return $this->belongsTo(User::class, 'updated_by');

    }



    public function cancelledBy()

    {

        return $this->belongsTo(User::class, 'cancelled_by');

    }



    public function savingLedgers()

    {

        return $this->hasMany(LoanSavingLedger::class);

    }



    public function isActive(): bool

    {

        return (int) $this->status === self::STATUS_ACTIVE;

    }



    public function isCancelled(): bool

    {

        return (int) $this->status === self::STATUS_CANCELLED;

    }

}


