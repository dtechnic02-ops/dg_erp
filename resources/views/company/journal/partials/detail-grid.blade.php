@php
    if (!isset($detailRows)) {
        if (old('account_id')) {
            $detailRows = collect(old('account_id'))->map(function ($accountId, $index) {
                return [
                    'account_id'    => $accountId,
                    'sub_ledger_id' => old('sub_ledger_id.' . $index, ''),
                    'debit'         => old('debit.' . $index, ''),
                    'credit'        => old('credit.' . $index, ''),
                    'note'          => old('row_note.' . $index, ''),
                ];
            })->values()->all();
        } else {
            $detailRows = array_fill(0, 2, [
                'account_id'    => '',
                'sub_ledger_id' => '',
                'debit'         => '',
                'credit'        => '',
                'note'          => '',
            ]);
        }
    }

    $chartAccounts = $chartAccounts ?? collect();
    $customers = $customers ?? collect();
    $suppliers = $suppliers ?? collect();
    $employees = $employees ?? collect();
    $parties = $parties ?? collect();

    $journalRelatedPartyOptions = [
        'customer' => $customers->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
        'supplier' => $suppliers->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
        'employee' => $employees->map(fn ($item) => ['id' => $item->id, 'name' => trim($item->full_name)])->values()->all(),
        'party'    => $parties->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
    ];

    $journalRelatedPartyPlaceholders = [
        'customer' => 'Select Customer',
        'supplier' => 'Select Supplier',
        'employee' => 'Select Employee',
        'party'    => 'Select Party',
    ];

    $formatJournalAccountBalance = static function ($balance): string {
        $balance = (float) $balance;

        if ($balance >= 0) {
            return 'Dr ' . number_format($balance, 2);
        }

        return 'Cr ' . number_format(abs($balance), 2);
    };

    $resolveRowBalanceDisplay = static function (array $row) use ($chartAccounts, $formatJournalAccountBalance): string {
        if (empty($row['account_id'])) {
            return '—';
        }

        $account = $chartAccounts->firstWhere('id', (int) $row['account_id']);

        if (!$account) {
            return '—';
        }

        return $formatJournalAccountBalance($account->current_balance);
    };
@endphp

<div class="dg-journal-details">
    <div class="dg-table-scroll">
        <table class="table dg-table dg-journal-table" id="journalDetailTable">
            <thead class="dg-head">
                <tr>
                    <th scope="col" class="dg-journal-col-num">#</th>
                    <th scope="col" class="dg-journal-col-account">Account</th>
                    <th scope="col" class="dg-journal-col-related-party">Related Party</th>
                    <th scope="col" class="dg-journal-col-balance dg-col-num">Balance</th>
                    <th scope="col" class="dg-journal-col-debit dg-col-num">Debit</th>
                    <th scope="col" class="dg-journal-col-credit dg-col-num">Credit</th>
                    <th scope="col" class="dg-journal-col-remark">Remark</th>
                    <th scope="col" class="dg-journal-col-action dg-action-col-compact d-print-none">Action</th>
                </tr>
            </thead>
            <tbody class="dg-body" id="journalDetailBody">
                @foreach ($detailRows as $index => $row)
                    <tr class="dg-row journal-detail-row">
                        <td class="journal-row-num dg-journal-col-num">{{ $loop->iteration }}</td>
                        <td class="dg-journal-col-account">
                            <select name="account_id[]" class="form-select dg-select journal-account-select" required aria-label="Account">
                                <option value="">Select Account</option>
                                @foreach ($chartAccounts as $chartAccount)
                                    <option
                                        value="{{ $chartAccount->id }}"
                                        data-balance="{{ $chartAccount->current_balance }}"
                                        data-balance-display="{{ $formatJournalAccountBalance($chartAccount->current_balance) }}"
                                        data-account-name="{{ $chartAccount->account_name }}"
                                        data-sub-ledger-type="{{ $chartAccount->sub_ledger_type ?? '' }}"
                                        @selected((string) $row['account_id'] === (string) $chartAccount->id)
                                    >{{ $chartAccount->account_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="dg-journal-col-related-party">
                            <select name="sub_ledger_id[]" class="form-select dg-select journal-related-party-select" aria-label="Related Party" hidden>
                                <option value="">Select</option>
                            </select>
                            @if (!empty($row['sub_ledger_id']))
                                <span class="journal-related-party-initial-value" data-value="{{ $row['sub_ledger_id'] }}" hidden></span>
                            @endif
                        </td>
                        <td class="dg-journal-col-balance dg-col-num">
                            <span class="journal-balance-display">{{ $resolveRowBalanceDisplay($row) }}</span>
                        </td>
                        <td class="dg-journal-col-debit dg-col-num">
                            <input type="number" name="debit[]" class="form-control dg-input journal-debit text-end" step="0.01" min="0" value="{{ $row['debit'] }}" placeholder="0.00" aria-label="Debit">
                        </td>
                        <td class="dg-journal-col-credit dg-col-num">
                            <input type="number" name="credit[]" class="form-control dg-input journal-credit text-end" step="0.01" min="0" value="{{ $row['credit'] }}" placeholder="0.00" aria-label="Credit">
                        </td>
                        <td class="dg-journal-col-remark">
                            <input type="text" name="row_note[]" class="form-control dg-input" value="{{ $row['note'] }}" aria-label="Remark">
                        </td>
                        <td class="dg-journal-col-action dg-action-col-compact d-print-none">
                            <button type="button" class="btn btn-outline-danger dg-btn dg-journal-remove-btn journal-remove-row" aria-label="Remove row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3h6a1 1 0 0 1 1 1v1h4a1 1 0 1 1 0 2h-1.05l-1.03 13.04A2 2 0 0 1 15.93 23H8.07a2 2 0 0 1-1.99-1.96L5.05 7H4a1 1 0 0 1 0-2h4V4a1 1 0 0 1 1-1zm1 2h4V5h-4v0zm-2.86 2 1 12.01h9.72L17.86 7H7.14zM10 9.5a1 1 0 0 1 1 1v7a1 1 0 1 1-2 0v-7a1 1 0 0 1 1-1zm4 0a1 1 0 0 1 1 1v7a1 1 0 1 1-2 0v-7a1 1 0 0 1 1-1z"/></svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="dg-journal-toolbar">
        <button type="button" id="journalAddRow" class="btn btn-outline-primary dg-btn dg-journal-add-btn">
            <span aria-hidden="true">➕</span> Add Row
        </button>
    </div>

    <div class="dg-summary-bar dg-journal-summary" aria-live="polite">
        <div class="dg-summary-bar-row">
            <div class="dg-summary-bar-item">
                <span class="dg-summary-bar-label">Total Debit</span>
                <span class="dg-summary-bar-sep" aria-hidden="true">:</span>
                <span class="dg-summary-bar-value" id="journalDebitTotal">0.00</span>
            </div>
            <div class="dg-summary-bar-item">
                <span class="dg-summary-bar-label">Total Credit</span>
                <span class="dg-summary-bar-sep" aria-hidden="true">:</span>
                <span class="dg-summary-bar-value" id="journalCreditTotal">0.00</span>
            </div>
            <div class="dg-summary-bar-item">
                <span class="dg-summary-bar-label">Difference</span>
                <span class="dg-summary-bar-sep" aria-hidden="true">:</span>
                <span class="dg-summary-bar-value" id="journalDifference">0.00</span>
            </div>
            <div class="dg-summary-bar-item">
                <span class="dg-summary-bar-label">Status</span>
                <span class="dg-summary-bar-sep" aria-hidden="true">:</span>
                <span class="dg-summary-bar-value" id="journalBalanceStatus">🔴 Unbalanced</span>
            </div>
        </div>
    </div>
</div>

<template id="journalDetailRowTemplate">
    <tr class="dg-row journal-detail-row">
        <td class="journal-row-num dg-journal-col-num">0</td>
        <td class="dg-journal-col-account">
            <select name="account_id[]" class="form-select dg-select journal-account-select" required aria-label="Account">
                <option value="">Select Account</option>
                @foreach ($chartAccounts as $chartAccount)
                    <option
                        value="{{ $chartAccount->id }}"
                        data-balance="{{ $chartAccount->current_balance }}"
                        data-balance-display="{{ $formatJournalAccountBalance($chartAccount->current_balance) }}"
                        data-account-name="{{ $chartAccount->account_name }}"
                        data-sub-ledger-type="{{ $chartAccount->sub_ledger_type ?? '' }}"
                    >{{ $chartAccount->account_name }}</option>
                @endforeach
            </select>
        </td>
        <td class="dg-journal-col-related-party">
            <select name="sub_ledger_id[]" class="form-select dg-select journal-related-party-select" aria-label="Related Party" hidden>
                <option value="">Select</option>
            </select>
        </td>
        <td class="dg-journal-col-balance dg-col-num">
            <span class="journal-balance-display">—</span>
        </td>
        <td class="dg-journal-col-debit dg-col-num">
            <input type="number" name="debit[]" class="form-control dg-input journal-debit text-end" step="0.01" min="0" placeholder="0.00" aria-label="Debit">
        </td>
        <td class="dg-journal-col-credit dg-col-num">
            <input type="number" name="credit[]" class="form-control dg-input journal-credit text-end" step="0.01" min="0" placeholder="0.00" aria-label="Credit">
        </td>
        <td class="dg-journal-col-remark">
            <input type="text" name="row_note[]" class="form-control dg-input" aria-label="Remark">
        </td>
        <td class="dg-journal-col-action dg-action-col-compact d-print-none">
            <button type="button" class="btn btn-outline-danger dg-btn dg-journal-remove-btn journal-remove-row" aria-label="Remove row">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3h6a1 1 0 0 1 1 1v1h4a1 1 0 1 1 0 2h-1.05l-1.03 13.04A2 2 0 0 1 15.93 23H8.07a2 2 0 0 1-1.99-1.96L5.05 7H4a1 1 0 0 1 0-2h4V4a1 1 0 0 1 1-1zm1 2h4V5h-4v0zm-2.86 2 1 12.01h9.72L17.86 7H7.14zM10 9.5a1 1 0 0 1 1 1v7a1 1 0 1 1-2 0v-7a1 1 0 0 1 1-1zm4 0a1 1 0 0 1 1 1v7a1 1 0 1 1-2 0v-7a1 1 0 0 1 1-1z"/></svg>
            </button>
        </td>
    </tr>
</template>

@push('styles')
<style>
.dg-journal-entry .form-label {
    font-size: 13px;
    margin-bottom: 4px;
}

.dg-journal-entry .dg-journal-header-row {
    --bs-gutter-y: 0.5rem;
}

.dg-journal-entry .dg-input,
.dg-journal-entry .dg-select,
.dg-journal-details .dg-input,
.dg-journal-details .dg-select {
    min-height: 32px;
    height: 32px;
    padding: 4px 10px;
    font-size: 13px;
    border-radius: 6px;
}

.dg-journal-entry textarea.dg-input {
    height: 76px;
    min-height: 76px;
    resize: vertical;
    padding: 8px 10px;
    line-height: 1.4;
}

.dg-journal-entry .dg-btn,
.dg-journal-details .dg-btn {
    min-height: 32px;
    height: 32px;
    padding: 4px 12px;
    font-size: 13px;
    border-radius: 6px;
}

.dg-journal-details .dg-journal-table thead th {
    font-size: 13px;
    font-weight: 700;
    padding: 6px 8px;
    vertical-align: middle;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    white-space: nowrap;
}

.dg-journal-details .dg-journal-table tbody td {
    padding: 5px 8px;
    vertical-align: middle;
    font-size: 13px;
}

.dg-journal-details .dg-journal-table tbody tr {
    height: 42px;
}

.dg-journal-details .dg-journal-col-num {
    width: 40px;
    min-width: 40px;
    max-width: 40px;
    text-align: center;
}

.dg-journal-details .dg-journal-col-account {
    width: 220px;
    min-width: 180px;
}

.dg-journal-details .dg-journal-col-related-party {
    width: 200px;
    min-width: 160px;
}

.dg-journal-details .dg-journal-col-balance {
    width: 130px;
    min-width: 110px;
}

.dg-journal-details .dg-journal-col-debit,
.dg-journal-details .dg-journal-col-credit {
    width: 120px;
    min-width: 100px;
}

.dg-journal-details .dg-journal-col-remark {
    width: 220px;
    min-width: 160px;
}

.dg-journal-details .dg-journal-col-action {
    width: 90px;
    min-width: 70px;
    text-align: center;
}

.dg-journal-details .journal-balance-display {
    display: block;
    font-size: 12px;
    line-height: 1.3;
    color: #6c757d;
    white-space: nowrap;
}

.dg-journal-details .dg-journal-remove-btn {
    width: 32px;
    min-width: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.dg-journal-details .dg-journal-toolbar {
    margin-top: 8px;
}

.dg-journal-details .dg-journal-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.dg-journal-details .dg-journal-summary {
    margin-top: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.dg-journal-details .dg-journal-summary .dg-summary-bar-item {
    font-size: 13px;
}

.dg-journal-details .dg-journal-summary .dg-summary-bar-value {
    font-size: 13px;
    font-weight: 700;
}

.dg-journal-details .dg-journal-summary .dg-summary-bar-value.text-danger {
    color: #dc3545 !important;
}

.dg-journal-details .dg-journal-summary .dg-summary-bar-value.text-success {
    color: #198754 !important;
}

.dg-journal-entry .dg-journal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

@media (max-width: 767.98px) {
    .dg-journal-details .dg-journal-table thead th,
    .dg-journal-details .dg-journal-table tbody td {
        font-size: 12px;
        padding: 4px 6px;
    }

    .dg-journal-details .dg-journal-summary .dg-summary-bar-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tableBody = document.getElementById('journalDetailBody');
    var template = document.getElementById('journalDetailRowTemplate');
    var addButton = document.getElementById('journalAddRow');
    var saveButton = document.getElementById('journalSaveBtn');
    var journalRelatedPartyOptions = @json($journalRelatedPartyOptions);
    var journalRelatedPartyPlaceholders = @json($journalRelatedPartyPlaceholders);

    function formatAmount(value) {
        return (Math.round(value * 100) / 100).toFixed(2);
    }

    function renumberRows() {
        tableBody.querySelectorAll('.journal-detail-row').forEach(function (row, index) {
            var cell = row.querySelector('.journal-row-num');
            if (cell) {
                cell.textContent = String(index + 1);
            }
        });
    }

    function calculateJournalTotals() {
        var debit = 0;
        var credit = 0;

        tableBody.querySelectorAll('.journal-detail-row').forEach(function (row) {
            debit += parseFloat(row.querySelector('.journal-debit')?.value || 0) || 0;
            credit += parseFloat(row.querySelector('.journal-credit')?.value || 0) || 0;
        });

        var debitFormatted = formatAmount(debit);
        var creditFormatted = formatAmount(credit);
        var difference = Math.abs(debit - credit);
        var differenceFormatted = formatAmount(difference);
        var isBalanced = debitFormatted === creditFormatted;

        document.getElementById('journalDebitTotal').textContent = debitFormatted;
        document.getElementById('journalCreditTotal').textContent = creditFormatted;

        var differenceEl = document.getElementById('journalDifference');
        differenceEl.textContent = isBalanced ? '0.00' : differenceFormatted;
        differenceEl.classList.toggle('text-danger', !isBalanced);
        differenceEl.classList.toggle('text-success', isBalanced);

        var balanceEl = document.getElementById('journalBalanceStatus');
        if (isBalanced) {
            balanceEl.textContent = '✅ Balanced';
            balanceEl.classList.remove('text-danger');
            balanceEl.classList.add('text-success');
        } else {
            balanceEl.textContent = '🔴 Unbalanced';
            balanceEl.classList.remove('text-success');
            balanceEl.classList.add('text-danger');
        }

        if (saveButton) {
            saveButton.disabled = !isBalanced;
        }
    }

    function updateRowAccountBalance(row) {
        var select = row.querySelector('.journal-account-select');
        var display = row.querySelector('.journal-balance-display');

        if (!select || !display) {
            return;
        }

        var option = select.options[select.selectedIndex];

        if (!option || !option.value) {
            display.textContent = '—';
            return;
        }

        display.textContent = option.getAttribute('data-balance-display') || '—';
    }

    function updateRowRelatedParty(row, preferredValue) {
        var accountSelect = row.querySelector('.journal-account-select');
        var relatedPartySelect = row.querySelector('.journal-related-party-select');
        var initialValueEl = row.querySelector('.journal-related-party-initial-value');

        if (!accountSelect || !relatedPartySelect) {
            return;
        }

        var option = accountSelect.options[accountSelect.selectedIndex];
        var relatedPartyType = option?.getAttribute('data-sub-ledger-type') || '';
        var selectedValue = preferredValue ?? initialValueEl?.getAttribute('data-value') ?? relatedPartySelect.value;

        if (initialValueEl) {
            initialValueEl.remove();
        }

        relatedPartySelect.innerHTML = '';
        relatedPartySelect.removeAttribute('required');
        relatedPartySelect.hidden = true;
        relatedPartySelect.value = '';

        if (!option?.value || !relatedPartyType) {
            return;
        }

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = journalRelatedPartyPlaceholders[relatedPartyType] || 'Select';
        relatedPartySelect.appendChild(placeholder);

        (journalRelatedPartyOptions[relatedPartyType] || []).forEach(function (item) {
            var partyOption = document.createElement('option');
            partyOption.value = String(item.id);
            partyOption.textContent = item.name;
            relatedPartySelect.appendChild(partyOption);
        });

        relatedPartySelect.hidden = false;
        relatedPartySelect.required = true;

        if (selectedValue) {
            relatedPartySelect.value = String(selectedValue);
        }
    }

    function bindAccountBalance(row) {
        var select = row.querySelector('.journal-account-select');

        select?.addEventListener('change', function () {
            updateRowAccountBalance(row);
            updateRowRelatedParty(row, '');
        });

        updateRowAccountBalance(row);
        updateRowRelatedParty(row);
    }

    function bindDebitCreditExclusion(row) {
        var debitInput = row.querySelector('.journal-debit');
        var creditInput = row.querySelector('.journal-credit');

        debitInput?.addEventListener('input', function () {
            if (parseFloat(debitInput.value || 0) > 0) {
                creditInput.value = '';
            }
            calculateJournalTotals();
        });

        creditInput?.addEventListener('input', function () {
            if (parseFloat(creditInput.value || 0) > 0) {
                debitInput.value = '';
            }
            calculateJournalTotals();
        });
    }

    function bindJournalDetailRow(row) {
        bindDebitCreditExclusion(row);
        bindAccountBalance(row);
    }

    tableBody.querySelectorAll('.journal-detail-row').forEach(bindJournalDetailRow);

    addButton?.addEventListener('click', function () {
        var clone = template.content.firstElementChild.cloneNode(true);
        tableBody.appendChild(clone);
        bindJournalDetailRow(clone);
        renumberRows();
        calculateJournalTotals();
    });

    tableBody.addEventListener('click', function (event) {
        var removeButton = event.target.closest('.journal-remove-row');

        if (!removeButton) {
            return;
        }

        if (tableBody.querySelectorAll('.journal-detail-row').length <= 2) {
            alert('At least two detail rows are required.');
            return;
        }

        removeButton.closest('.journal-detail-row')?.remove();
        renumberRows();
        calculateJournalTotals();
    });

    calculateJournalTotals();
});
</script>
@endpush
