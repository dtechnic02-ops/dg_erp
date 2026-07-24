<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeAccount extends Model
{
    public const STATUS_INACTIVE = 0;

    public const STATUS_ACTIVE = 1;

    protected $fillable = [
        'company_id',
        'employee_code',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'address',
        'gender',
        'dob',
        'joining_date',
        'designation',
        'department',
        'post',
        'employment_type',
        'basic_salary',
        'salary_type',
        'opening_due_salary',
        'bank_name',
        'bank_account_no',
        'account_holder_name',
        'cit_no',
        'pan_no',
        'emergency_contact',
        'emergency_phone',
        'photo',
        'cv_attachment',
        'id_document',
        'contract_document',
        'note',
        'created_by',
        'updated_by',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function salarySheets()
    {
        return $this->hasMany(SalarySheet::class, 'employee_id');
    }

    public function employeePayments()
    {
        return $this->hasMany(EmployeePayment::class, 'employee_account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name
            . ' '
            . ($this->middle_name ?? '')
            . ' '
            . ($this->last_name ?? '')
        );
    }

    public function hasHrDependencies(): bool
    {
        if ($this->salarySheets()->exists()) {
            return true;
        }

        if (DB::table('employee_payments')->where('employee_account_id', $this->id)->exists()) {
            return true;
        }

        if (class_exists(\App\Models\Attendance::class)) {
            if (\App\Models\Attendance::where('employee_id', $this->id)->exists()) {
                return true;
            }
        }

        if (class_exists(\App\Models\Leave::class)) {
            if (\App\Models\Leave::where('employee_id', $this->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function dependencySummary(): array
    {
        $dependencies = [];

        $salarySheetCount = $this->salarySheets()->count();
        if ($salarySheetCount > 0) {
            $dependencies[] = $salarySheetCount . ' salary sheet(s)';
        }

        $paymentCount = (int) DB::table('employee_payments')
            ->where('employee_account_id', $this->id)
            ->count();
        if ($paymentCount > 0) {
            $dependencies[] = $paymentCount . ' employee payment(s)';
        }

        if (class_exists(\App\Models\Attendance::class)) {
            $attendanceCount = \App\Models\Attendance::where('employee_id', $this->id)->count();
            if ($attendanceCount > 0) {
                $dependencies[] = $attendanceCount . ' attendance record(s)';
            }
        }

        if (class_exists(\App\Models\Leave::class)) {
            $leaveCount = \App\Models\Leave::where('employee_id', $this->id)->count();
            if ($leaveCount > 0) {
                $dependencies[] = $leaveCount . ' leave record(s)';
            }
        }

        return $dependencies;
    }
}
