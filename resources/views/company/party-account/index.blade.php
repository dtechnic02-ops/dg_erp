@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Party Account Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Current Balance</span>
                            <span class="fw-bold">{{ number_format($totalCurrentBalance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Party</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Search Party"
                            class="form-control form-control-sm dg-input">
                        <button type="submit" class="btn btn-sm btn-primary dg-btn">Search</button>
                    </form>

                    <nav class="btn-group flex-wrap" aria-label="Party account toolbar">
                        <button
                            type="button"
                            class="btn btn-sm btn-success dg-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#partyModal">
                            Add Party
                        </button>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <h2 class="h6 mb-0">Party Account List</h2>

                            <form method="GET" class="d-flex align-items-center gap-2 mb-0">
                                <input type="hidden" name="search" value="{{ request('search') }}">

                                <label for="per_page" class="mb-0 fw-bold">Per Page:</label>
                                <select
                                    name="per_page"
                                    id="per_page"
                                    class="form-select form-select-sm dg-select w-auto"
                                    onchange="this.form.submit()">
                                    @foreach ([10, 25, 50, 100, 200, 500] as $size)
                                        <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Account No</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Phone</th>
                                        <th scope="col">Type</th>
                                        <th scope="col" class="text-end">Current Balance</th>
                                        <th scope="col">Due Date</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="90">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($parties as $party)
                                        <tr class="dg-row">
                                            <td>{{ $party->account_no }}</td>
                                            <td>{{ $party->name }}</td>
                                            <td>{{ $party->phone ?: '-' }}</td>
                                            <td>{{ ucfirst($party->type) }}</td>
                                            <td class="text-end">{{ number_format($party->current_balance, 2) }}</td>
                                            <td>{{ optional($party->due_date)->format('Y-m-d') ?: '-' }}</td>
                                            <td>
                                                @if ($party->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a
                                                    href="{{ route('company.party-account.show', $party->id) }}"
                                                    class="btn btn-sm btn-outline-primary dg-btn">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="8" class="text-center">No Party Accounts Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $parties->firstItem() ?? 0 }} to {{ $parties->lastItem() ?? 0 }} of {{ $parties->total() }} records
                            </p>

                            <nav aria-label="Party account pagination">
                                {{ $parties->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

<div class="modal fade" id="partyModal" tabindex="-1" aria-labelledby="addPartyLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form
                method="POST"
                enctype="multipart/form-data"
                action="{{ route('company.party-account.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="addPartyLabel">Add Party Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.party-account.form', ['mode' => 'create', 'accountNo' => $accountNo])
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
