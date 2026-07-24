<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;



class CrmOpportunity extends Model

{

    protected $fillable = [

        'company_id',

        'financial_year_id',

        'opportunity_no',

        'crm_lead_id',

        'customer_id',

        'title',

        'potential_value',

        'expected_closing_date',

        'probability',

        'stage',

        'assigned_employee_id',

        'status',

        'remarks',

        'closed_by',

        'closed_at',

        'close_reason',

        'archived_by',

        'archived_at',

        'archive_reason',

        'cancelled_by',

        'cancelled_at',

        'cancel_reason',

        'created_by',

        'updated_by',

    ];



    protected $casts = [

        'expected_closing_date' => 'date',

        'potential_value' => 'decimal:2',

        'probability' => 'decimal:2',

        'closed_at' => 'datetime',

        'archived_at' => 'datetime',

        'cancelled_at' => 'datetime',

    ];



    public function company(): BelongsTo

    {

        return $this->belongsTo(Company::class);

    }



    public function financialYear(): BelongsTo

    {

        return $this->belongsTo(FinancialYear::class);

    }



    public function lead(): BelongsTo

    {

        return $this->belongsTo(CrmLead::class, 'crm_lead_id');

    }



    public function customer(): BelongsTo

    {

        return $this->belongsTo(Customer::class);

    }



    public function assignedEmployee(): BelongsTo

    {

        return $this->belongsTo(EmployeeAccount::class, 'assigned_employee_id');

    }



    public function followUps(): HasMany

    {

        return $this->hasMany(CrmFollowUp::class, 'crm_opportunity_id');

    }



    public function isActive(array $terminalStatusKeys): bool

    {

        return !in_array($this->status, $terminalStatusKeys, true)

            && !$this->archived_at

            && !$this->cancelled_at;

    }



    public function isEditable(array $terminalStatusKeys): bool

    {

        return $this->isActive($terminalStatusKeys);

    }

}


