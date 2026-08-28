<?php

namespace App\Http\Requests;

use App\Models\OpeningBalance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'financial_year_id' => ['required', 'integer'],
            'business_date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', Rule::in(OpeningBalance::TYPES)],
            'reference_number' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'request_key' => [$this->isMethod('post') ? 'required' : 'nullable', 'uuid'],
            'new_account_id' => ['required_if:type,new_account', 'nullable', 'integer', 'min:1'],
            'new_account_amount' => ['required_if:type,new_account', 'nullable', 'decimal:0,4', 'gt:0'],
            'lines' => ['required_unless:type,new_account', 'nullable', 'array', 'min:2'],
            'lines.*.account_picker' => ['required_without:lines.*.chart_account_id', 'nullable', 'string', 'regex:/^(operational|customer|supplier|chart):[1-9][0-9]*$/'],
            'lines.*.chart_account_id' => ['required_without:lines.*.account_picker', 'nullable', 'integer'],
            'lines.*.operational_account_id' => ['nullable', 'integer'],
            'lines.*.debit' => ['nullable', 'decimal:0,4'],
            'lines.*.credit' => ['nullable', 'decimal:0,4'],
            'lines.*.subledger_type' => ['nullable', Rule::in(['customer', 'supplier'])],
            'lines.*.subledger_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.line_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
