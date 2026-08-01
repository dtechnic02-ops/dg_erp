<?php

namespace App\Http\Requests;

use App\Models\Journal;
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
            'lines.*.subledger_type' => ['nullable', 'string', 'max:30'],
            'lines.*.subledger_id' => ['nullable', 'integer'],
        ];
    }
}
