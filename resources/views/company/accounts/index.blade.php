@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Account Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Current Balance</span>
                            <span class="fw-bold">{{ number_format($totalCurrentBalance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <label for="account_group_filter" class="visually-hidden">Filter by Account Group</label>
                        <select name="account_group" id="account_group_filter" class="form-select dg-select">
                            <option value="">All Groups</option>
                            @foreach ($accountGroups as $value => $label)
                                <option value="{{ $value }}" {{ request('account_group') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label for="search" class="visually-hidden">Search Accounts</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Accounts" class="form-control dg-input">
                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.accounts.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#accountModal">Add Account</button>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Account List</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Image</th>
                                        <th scope="col">Account Group</th>
                                        <th scope="col">Account Type</th>
                                        <th scope="col">Account Name</th>
                                        <th scope="col">Current Balance</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($accounts as $account)
                                        <tr class="dg-row">
                                            <td>
                                                @if ($account->image_path)
                                                    <img src="{{ asset($account->image_path) }}" alt="{{ $account->account_name }}" width="40" height="40">
                                                @endif
                                            </td>
                                            <td>{{ $account->account_group ?: '-' }}</td>
                                            <td>{{ $account->account_type }}</td>
                                            <td>{{ $account->account_name }}</td>
                                            <td class="text-end">{{ number_format($account->current_balance, 2) }}</td>
                                            <td>
                                                @if ($account->status == 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Account actions">
                                                    <button type="button" class="btn btn-sm btn-outline-success dg-btn" data-bs-toggle="modal" data-bs-target="#edit{{ $account->id }}">Edit</button>

                                                    <a href="{{ route('company.accounts.show', $account->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>

                                                    <form method="POST" action="{{ route('company.accounts.delete', $account->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Delete this account?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="7" class="text-center">No Accounts Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label="Account list pagination" class="mt-3">
                            {{ $accounts->links() }}
                        </nav>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

{{-- Edit Account Modals --}}
@foreach ($accounts as $account)
    <div class="modal fade" id="edit{{ $account->id }}" tabindex="-1" aria-labelledby="editAccountLabel{{ $account->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" action="{{ route('company.accounts.update', $account->id) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="editAccountLabel{{ $account->id }}">Edit Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @include('company.accounts.form', [
                            'account' => $account,
                            'formPrefix' => 'edit-' . $account->id,
                            'formMode' => 'edit',
                        ])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary dg-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Add Account Modal --}}
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="addAccountLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" action="{{ route('company.accounts.store') }}" id="addAccountForm">
                @csrf
                <input type="hidden" name="_account_form" value="create">

                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountLabel">Add Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.accounts.form', [
                        'formPrefix' => 'add',
                        'formMode' => 'create',
                    ])
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('accountModal');
    const addForm = document.getElementById('addAccountForm');

    if (!addModal || !addForm) {
        return;
    }

    addModal.addEventListener('show.bs.modal', function () {
        if (addForm.dataset.preserveAfterValidation === '1') {
            addForm.dataset.preserveAfterValidation = '0';
            return;
        }

        addForm.reset();

        const currency = addForm.querySelector('#add_currency');
        if (currency) {
            currency.value = 'AED';
        }

        const status = addForm.querySelector('#add_status');
        if (status) {
            status.value = 'active';
        }

        const balance = addForm.querySelector('#add_current_balance');
        if (balance) {
            balance.value = '0';
        }

        const hiddenMarker = addForm.querySelector('input[name="_account_form"]');
        if (hiddenMarker) {
            hiddenMarker.value = 'create';
        }

        const preview = addForm.querySelector('.account-image-preview');
        if (preview) {
            preview.innerHTML = '';
        }

        const fileInput = addForm.querySelector('.account-image-input');
        if (fileInput) {
            fileInput.value = '';
        }
    });
});
</script>
@if ($errors->any() && old('_account_form') === 'create')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addForm = document.getElementById('addAccountForm');
    const addModal = document.getElementById('accountModal');

    if (addForm) {
        addForm.dataset.preserveAfterValidation = '1';
    }

    if (addModal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(addModal).show();
    }
});
</script>
@endif
@endpush

@endsection
