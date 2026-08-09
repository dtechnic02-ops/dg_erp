<?php

namespace App\Http\Requests;

use App\Models\Journal;
use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('id') ? 'journal.edit-draft' : 'journal.create';
        $legacy = $this->route('id') ? 'edit_journal' : 'create_journal';
        return (bool) ($this->user()?->hasPermission($permission) || $this->user()?->hasPermission($legacy));
    }

    public function rules(): array
    {
        return [
            'financial_year_id' => ['required', 'integer'],
            'journal_date' => ['required', 'date_format:Y-m-d'],
            'journal_type' => ['required', Rule::in(Journal::TYPES)],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:4000'],
            'request_key' => [$this->route('id') ? 'nullable' : 'required', 'uuid'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_account_id' => ['required', 'integer'],
            'lines.*.account_id' => ['nullable', 'integer'],
            'lines.*.debit' => ['required', 'regex:/^\d{1,16}(\.\d{1,4})?$/'],
            'lines.*.credit' => ['required', 'regex:/^\d{1,16}(\.\d{1,4})?$/'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.reference' => ['nullable', 'string', 'max:100'],
            'lines.*.subledger_type' => ['nullable', 'required_with:lines.*.subledger_id', Rule::in(['customer', 'supplier'])],
            'lines.*.subledger_id' => ['nullable', 'integer', 'required_with:lines.*.subledger_type'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $companyId = $this->user()?->company_id;
            if (! $companyId) return;

            foreach ((array) $this->input('lines', []) as $index => $line) {
                $chartAccount = ChartAccount::where('company_id', $companyId)->find($line['chart_account_id'] ?? null);
                if (! $chartAccount) continue;

                $type = $line['subledger_type'] ?? null;
                $subledgerId = $line['subledger_id'] ?? null;
                if ($type === 'customer') {
                    if ($chartAccount->system_code !== 'ACCOUNTS_RECEIVABLE' || ! Customer::where('company_id', $companyId)->where('status', 'active')->whereKey($subledgerId)->exists()) $validator->errors()->add("lines.{$index}.subledger_id", 'Customer subledger requires the company Accounts Receivable control Chart Account.');
                }
                if ($type === 'supplier') {
                    if ($chartAccount->system_code !== 'ACCOUNTS_PAYABLE' || ! Supplier::where('company_id', $companyId)->where('status', 'active')->whereKey($subledgerId)->exists()) $validator->errors()->add("lines.{$index}.subledger_id", 'Supplier subledger requires the company Accounts Payable control Chart Account.');
                }

                if (! empty($line['account_id'])) {
                    $account = Account::where('company_id', $companyId)->whereKey($line['account_id'])->whereIn('status', [1, 'active'])->first();
                    $requiredCode = match ($account?->account_type) {
                        'Cash' => 'CASH_IN_HAND',
                        'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
                        default => null,
                    };
                    if (! $requiredCode || $chartAccount->system_code !== $requiredCode) $validator->errors()->add("lines.{$index}.account_id", 'Operational Account must match its required Cash or Bank Chart Account.');
                }
            }
        });
    }
}
