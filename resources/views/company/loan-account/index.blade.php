@extends('company.layout')

@section('title', 'Loan Ledger')

@section('content')

@php
    $canCreate = userCan('create_loan_account');
    $canView = userCan('view_loan_account');
    $canPrint = userCan('print_loan_account');
    $canViewPayment = userCan('view_loan_payment');
@endphp

<div class="dg-page loan-ledger-page @if (request('print')) dg-print-page @endif">

    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Loan Ledger</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group flex-wrap" aria-label="Loan ledger toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        @if ($canCreate)
                            <a href="{{ route('company.loan-account.create') }}" class="btn btn-success dg-btn">New Loan</a>
                        @endif
                        @if ($canViewPayment)
                            <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Loan Payment</a>
                        @endif
                        <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        @if ($canPrint)
                            <button type="button" class="btn btn-outline-secondary dg-btn" onclick="window.print()">Print</button>
                        @endif
                        <button type="button" class="btn btn-outline-secondary dg-btn" id="loanLedgerExportBtn">Export</button>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section @if (request('print')) d-print-none @endif">
                <div class="row g-2">
                    <div class="col-md-4 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Total Loans</span>
                                    <span class="fw-bold fs-5">{{ number_format($totalLoans) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-4 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Total Principal Amount</span>
                                    <span class="fw-bold fs-5">{{ number_format($totalPrincipalAmount, 2) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-4 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Total Remaining Principal</span>
                                    <span class="fw-bold fs-5">{{ number_format($totalRemainingPrincipal, 2) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-6 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Active Loans</span>
                                    <span class="fw-bold fs-5 text-success">{{ number_format($activeLoans) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-6 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Closed Loans</span>
                                    <span class="fw-bold fs-5 text-secondary">{{ number_format($closedLoans) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-6 col-lg">
                        <article class="card dg-card h-100">
                            <div class="card-body dg-card-body py-2">
                                <div class="dg-summary-item mb-0 border-0 p-0">
                                    <span class="dg-label d-block mb-1">Cancelled Loans</span>
                                    <span class="fw-bold fs-5 text-danger">{{ number_format($cancelledLoans) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="dg-section dg-filter @if (request('print')) d-print-none @endif">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.loan-account.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input
                                        type="text"
                                        name="search"
                                        id="search"
                                        class="form-control dg-input dg-filter-control dg-search"
                                        value="{{ request('search') }}"
                                        placeholder="Loan No / Party / Loan Name / Phone">
                                </div>

                                <div class="dg-filter-field">
                                    <label for="loan_no" class="dg-filter-label">Loan No</label>
                                    <input
                                        type="text"
                                        name="loan_no"
                                        id="loan_no"
                                        class="form-control dg-input dg-filter-control"
                                        value="{{ request('loan_no') }}"
                                        placeholder="Loan No">
                                </div>

                                <div class="dg-filter-field">
                                    <label for="party_account_id" class="dg-filter-label">Party</label>
                                    <select name="party_account_id" id="party_account_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Parties</option>
                                        @foreach ($partyAccounts as $party)
                                            <option value="{{ $party->id }}" @selected(request('party_account_id') == $party->id)>{{ $party->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="loan_type" class="dg-filter-label">Loan Type</label>
                                    <select name="loan_type" id="loan_type" class="form-select dg-select dg-filter-control">
                                        <option value="">All Types</option>
                                        <option value="taken" @selected(request('loan_type') === 'taken')>Taken</option>
                                        <option value="given" @selected(request('loan_type') === 'given')>Given</option>
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-account">
                                    <label for="account_id" class="dg-filter-label">Account</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="active" @selected(request('status', 'active') === 'active')>Active</option>
                                        <option value="all" @selected(request('status') === 'all')>All Status</option>
                                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="date_from" class="dg-filter-label">Date From</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control dg-input dg-filter-control" value="{{ request('date_from') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="date_to" class="dg-filter-label">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control dg-input dg-filter-control" value="{{ request('date_to') }}">
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header @if (request('print')) d-print-none @endif">
                        <h2 class="dg-list-card-title">Loan Ledger List</h2>

                        <form method="GET" action="{{ route('company.loan-account.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="loan_no" value="{{ request('loan_no') }}">
                            <input type="hidden" name="party_account_id" value="{{ request('party_account_id') }}">
                            <input type="hidden" name="loan_type" value="{{ request('loan_type') }}">
                            <input type="hidden" name="account_id" value="{{ request('account_id') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                @foreach ([10, 20, 50, 100, 200] as $size)
                                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </form>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table" id="loanLedgerTable">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Loan No</th>
                                        <th scope="col">Loan Date</th>
                                        <th scope="col">Party</th>
                                        <th scope="col">Loan Name</th>
                                        <th scope="col">Loan Type</th>
                                        <th scope="col">Cash/Bank Account</th>
                                        <th scope="col" class="text-end">Principal Amount</th>
                                        <th scope="col" class="text-end">Remaining Principal</th>
                                        <th scope="col" class="text-end">Interest Rate</th>
                                        <th scope="col">Due Date</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Created By</th>
                                        <th scope="col" class="dg-action-col d-print-none">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($loans as $loan)
                                        @php
                                            $isClosed = $loan->isActive() && (float) $loan->remaining_principal <= 0;
                                        @endphp

                                        <tr class="dg-row">
                                            <td>{{ $loans->firstItem() + $loop->index }}</td>
                                            <td>{{ $loan->loan_no }}</td>
                                            <td>{{ optional($loan->start_date)->format('d-m-Y') ?: '-' }}</td>
                                            <td>{{ $loan->partyAccount->name ?? '-' }}</td>
                                            <td>{{ $loan->loan_name }}</td>
                                            <td>
                                                @if ($loan->loan_type === 'taken')
                                                    <span class="badge bg-info">Taken</span>
                                                @else
                                                    <span class="badge bg-primary">Given</span>
                                                @endif
                                            </td>
                                            <td>{{ $loan->account->account_name ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($loan->principal_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($loan->remaining_principal, 2) }}</td>
                                            <td class="text-end">{{ number_format($loan->interest_rate, 2) }}%</td>
                                            <td>{{ optional($loan->end_date)->format('d-m-Y') ?: '-' }}</td>
                                            <td>
                                                @if ($loan->isCancelled())
                                                    <span class="badge bg-danger">Cancelled</span>
                                                @elseif ($isClosed)
                                                    <span class="badge bg-dark">Closed</span>
                                                @else
                                                    <span class="badge bg-success">Active</span>
                                                @endif
                                            </td>
                                            <td>{{ $loan->createdBy->name ?? '-' }}</td>
                                            <td class="dg-action-col d-print-none">
                                                @if ($canView)
                                                    <div class="dg-action-group" role="group" aria-label="Loan actions for {{ $loan->loan_no }}">
                                                        <a href="{{ route('company.loan-account.show', $loan->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="14" class="text-center">No loans found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 @if (request('print')) d-print-none @endif">
                            <p class="mb-0 text-muted">
                                Showing {{ $loans->firstItem() ?? 0 }} to {{ $loans->lastItem() ?? 0 }} of {{ $loans->total() }} records
                            </p>

                            <nav aria-label="Loan ledger pagination">
                                {{ $loans->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@push('scripts')
<script>
(function () {
    const exportBtn = document.getElementById('loanLedgerExportBtn');
    const table = document.getElementById('loanLedgerTable');

    if (!exportBtn || !table) {
        return;
    }

    exportBtn.addEventListener('click', function () {
        const rows = [];
        const headers = [];

        table.querySelectorAll('thead th').forEach(function (th, index) {
            if (index === table.querySelectorAll('thead th').length - 1) {
                return;
            }

            headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
        });

        rows.push(headers.join(','));

        table.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.querySelector('td[colspan]')) {
                return;
            }

            const cells = [];

            tr.querySelectorAll('td').forEach(function (td, index) {
                if (index === tr.querySelectorAll('td').length - 1) {
                    return;
                }

                cells.push('"' + td.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""') + '"');
            });

            rows.push(cells.join(','));
        });

        const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.href = url;
        link.download = 'loan-ledger-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
})();
</script>
@endpush

@endsection
