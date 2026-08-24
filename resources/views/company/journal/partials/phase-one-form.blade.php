@php
    $existing = $journal?->items ?? collect();
    $operationalAccounts = $operationalAccounts ?? collect();
    $rows = old('lines', $existing->map(fn ($line) => [
        'chart_account_id' => $line->chart_account_id, 'account_id' => $line->account_id,
        'debit' => $line->debit ?? ($line->type === 'debit' ? $line->amount : '0.0000'),
        'credit' => $line->credit ?? ($line->type === 'credit' ? $line->amount : '0.0000'),
        'description' => $line->description ?? $line->note, 'reference' => $line->reference,
    ])->all());
    if (count($rows) < 2) $rows = [['debit'=>'0.0000','credit'=>'0.0000'],['debit'=>'0.0000','credit'=>'0.0000']];
    $operationalAccountsJson = $operationalAccounts->map(function ($account) {
        return [
            'id' => $account->id,
            'name' => $account->account_name,
            'type' => $account->account_type,
        ];
    })->values()->all();
@endphp
<form method="POST" action="{{ $action }}" class="card dg-card">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <input type="hidden" name="request_key" value="{{ old('request_key', $journal?->request_key ?? $requestKey ?? '') }}">
    <div class="card-body dg-card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Financial Year</label><select name="financial_year_id" class="form-select dg-select" required><option value="{{ $journal?->financial_year_id ?? $activeFy->id }}">{{ $journal?->financialYear?->name ?? $activeFy->name }}</option></select></div>
            <div class="col-md-3"><label class="form-label">Business Date</label><input type="date" id="journal_date" name="journal_date" class="form-control dg-input" value="{{ old('journal_date', $journal?->journal_date?->format('Y-m-d') ?? '') }}" required></div>
            @include('company.components.nepali-date-field', ['adInputId' => 'journal_date', 'adDate' => old('journal_date', $journal?->journal_date)])
            <div class="col-md-3"><label class="form-label">Journal Type</label><select name="journal_type" class="form-select dg-select" required>@foreach(\App\Models\Journal::TYPES as $type)<option value="{{ $type }}" @selected(old('journal_type', $journal?->journal_type ?? 'general') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Reference</label><input name="reference_no" class="form-control dg-input" value="{{ old('reference_no', $journal?->reference_no) }}"></div>
            <div class="col-md-6"><label class="form-label">Description</label><textarea name="description" class="form-control dg-input" required>{{ old('description', $journal?->description ?? $journal?->note) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control dg-input">{{ old('remarks', $journal?->remarks) }}</textarea></div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table dg-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chart Account</th>
                        <th>Operational Account</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        <tr class="journal-line-row">
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <select name="lines[{{ $i }}][chart_account_id]" class="form-select dg-select journal-chart-account" required>
                                    <option value="">Select</option>
                                    @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}" data-system-code="{{ $account->system_code }}" @selected((int)old("lines.$i.chart_account_id", $row['chart_account_id'] ?? 0) === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="lines[{{ $i }}][account_id]" class="form-select dg-select journal-operational-account" data-selected="{{ old("lines.$i.account_id", $row['account_id'] ?? '') }}">
                                    <option value="">—</option>
                                </select>
                            </td>
                            <td><input name="lines[{{ $i }}][description]" class="form-control dg-input" value="{{ old("lines.$i.description", $row['description'] ?? '') }}"></td>
                            <td><input name="lines[{{ $i }}][reference]" class="form-control dg-input" value="{{ old("lines.$i.reference", $row['reference'] ?? '') }}"></td>
                            <td><input type="number" min="0" step="0.0001" name="lines[{{ $i }}][debit]" class="form-control dg-input text-end journal-debit" value="{{ old("lines.$i.debit", $row['debit'] ?? '0.0000') }}" required></td>
                            <td><input type="number" min="0" step="0.0001" name="lines[{{ $i }}][credit]" class="form-control dg-input text-end journal-credit" value="{{ old("lines.$i.credit", $row['credit'] ?? '0.0000') }}" required></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end gap-4"><span>Debit: <b id="debitTotal">0.0000</b></span><span>Credit: <b id="creditTotal">0.0000</b></span><span>Difference: <b id="difference">0.0000</b></span></div>
    </div>
    <div class="card-footer text-end"><button class="btn btn-primary dg-btn">Save Draft</button></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const operationalAccounts = @json($operationalAccountsJson);

    const bankTypes = ['Bank', 'ATM', 'Wallet'];

    function accountsForSystemCode(systemCode) {
        if (systemCode === 'CASH_IN_HAND') {
            return operationalAccounts.filter(account => account.type === 'Cash');
        }
        if (systemCode === 'BANK_ACCOUNTS') {
            return operationalAccounts.filter(account => bankTypes.includes(account.type));
        }
        return [];
    }

    function syncOperationalAccount(row) {
        const chartSelect = row.querySelector('.journal-chart-account');
        const accountSelect = row.querySelector('.journal-operational-account');
        if (!chartSelect || !accountSelect) return;

        const selectedChart = chartSelect.options[chartSelect.selectedIndex];
        const systemCode = selectedChart?.dataset.systemCode || '';
        const allowed = accountsForSystemCode(systemCode);
        const selectedValue = accountSelect.dataset.selected || accountSelect.value || '';

        accountSelect.innerHTML = '<option value="">—</option>';
        allowed.forEach(account => {
            const option = document.createElement('option');
            option.value = String(account.id);
            option.textContent = account.name + ' (' + account.type + ')';
            if (String(selectedValue) === String(account.id)) {
                option.selected = true;
            }
            accountSelect.appendChild(option);
        });

        const requiresOperational = systemCode === 'CASH_IN_HAND' || systemCode === 'BANK_ACCOUNTS';
        accountSelect.disabled = !requiresOperational;
        accountSelect.required = requiresOperational;

        if (!requiresOperational) {
            accountSelect.value = '';
            accountSelect.dataset.selected = '';
        } else if (!allowed.some(account => String(account.id) === String(accountSelect.value))) {
            accountSelect.value = '';
            accountSelect.dataset.selected = '';
        }
    }

    function updateTotals() {
        let debit = 0;
        let credit = 0;
        document.querySelectorAll('.journal-debit').forEach(el => debit += Number(el.value || 0));
        document.querySelectorAll('.journal-credit').forEach(el => credit += Number(el.value || 0));
        document.getElementById('debitTotal').textContent = debit.toFixed(4);
        document.getElementById('creditTotal').textContent = credit.toFixed(4);
        document.getElementById('difference').textContent = (debit - credit).toFixed(4);
    }

    document.querySelectorAll('.journal-line-row').forEach(row => {
        syncOperationalAccount(row);
        row.querySelector('.journal-chart-account')?.addEventListener('change', () => {
            const accountSelect = row.querySelector('.journal-operational-account');
            if (accountSelect) {
                accountSelect.dataset.selected = '';
                accountSelect.value = '';
            }
            syncOperationalAccount(row);
        });
        row.querySelector('.journal-operational-account')?.addEventListener('change', event => {
            event.target.dataset.selected = event.target.value;
        });
    });

    document.querySelectorAll('.journal-debit,.journal-credit').forEach(el => el.addEventListener('input', updateTotals));
    updateTotals();
});
</script>
