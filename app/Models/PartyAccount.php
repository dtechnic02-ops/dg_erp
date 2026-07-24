<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartyAccount extends Model
{
    use SoftDeletes;

    public const STATUS_INACTIVE = 0;

    public const STATUS_ACTIVE = 1;

    protected $fillable = [
        'company_id',
        'account_no',
        'name',
        'phone',
        'address',
        'opening_balance',
        'current_balance',
        'type',
        'photo',
        'id_card',
        'document',
        'note',
        'due_date',
        'created_by',
        'updated_by',
        'deleted_by',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'due_date'          => 'date',
        'status'            => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function loanAccounts()
    {
        return $this->hasMany(LoanAccount::class, 'party_account_id');
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }
}
