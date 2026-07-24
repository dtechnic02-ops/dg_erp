<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    public const TYPE_DEBIT = 'debit';

    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'company_id',
        'journal_id',
        'account_id',
        'sub_ledger_type',
        'sub_ledger_id',
        'type',
        'amount',
        'note',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'integer',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getDebitAmountAttribute(): float
    {
        return $this->type === self::TYPE_DEBIT ? (float) $this->amount : 0.0;
    }

    public function getCreditAmountAttribute(): float
    {
        return $this->type === self::TYPE_CREDIT ? (float) $this->amount : 0.0;
    }

    public function getSubLedgerLabelAttribute(): ?string
    {
        if (!$this->sub_ledger_type || !$this->sub_ledger_id) {
            return null;
        }

        $name = $this->resolveSubLedgerName();

        if (!$name) {
            return null;
        }

        $typeLabel = Account::subLedgerTypeLabels()[$this->sub_ledger_type] ?? ucfirst($this->sub_ledger_type);

        return $typeLabel . ': ' . $name;
    }

    public function resolveSubLedgerName(): ?string
    {
        if (!$this->sub_ledger_type || !$this->sub_ledger_id) {
            return null;
        }

        $companyId = $this->company_id;

        return match ($this->sub_ledger_type) {
            Account::SUB_LEDGER_CUSTOMER => Customer::where('company_id', $companyId)
                ->whereKey($this->sub_ledger_id)
                ->value('name'),
            Account::SUB_LEDGER_SUPPLIER => Supplier::where('company_id', $companyId)
                ->whereKey($this->sub_ledger_id)
                ->value('name'),
            Account::SUB_LEDGER_EMPLOYEE => optional(
                EmployeeAccount::where('company_id', $companyId)->find($this->sub_ledger_id)
            )->full_name,
            Account::SUB_LEDGER_PARTY => PartyAccount::where('company_id', $companyId)
                ->whereKey($this->sub_ledger_id)
                ->value('name'),
            default => null,
        };
    }
}
