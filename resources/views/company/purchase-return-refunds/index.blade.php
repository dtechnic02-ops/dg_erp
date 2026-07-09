@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- PAGE HEADER --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mb-3
    ">

        <div>
            

            <h4 class="mb-1">

                Purchase Return Refunds

            </h4>
            <a
    href="{{ route(
        'company.purchase-return-refunds.print',
        request()->query()
    ) }}"
    target="_blank"
    class="btn btn-success"
>
    <i class="fa fa-print"></i>
    Print
</a>

            <small class="text-muted">

                Refund payment history

            </small>

        </div>

    </div>

    {{-- FILTER --}}

    <div class="
        card
        border-0
        shadow-sm
        mb-2
    ">

        <div class="card-body">

            <form method="GET"
                  action="{{ route(
                        'company.purchase-return-refunds.index'
                  ) }}">

                <div class="row g-2">

                    {{-- START DATE --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Start Date

                        </label>

                        <input type="date"
                               name="start_date"
                               value="{{ request(
                                    'start_date'
                               ) }}"
                               class="form-control">

                    </div>

                    {{-- END DATE --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            End Date

                        </label>

                        <input type="date"
                               name="end_date"
                               value="{{ request(
                                    'end_date'
                               ) }}"
                               class="form-control">

                    </div>

                    {{-- SUPPLIER --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Supplier

                        </label>

                        <select name="supplier_id"
                                class="form-select">

                            <option value="">

                                All Suppliers

                            </option>

                            @foreach($suppliers as $supplier)

                                <option
                                    value="{{ $supplier->id }}"

                                    {{
                                        request(
                                            'supplier_id'
                                        ) == $supplier->id
                                        ? 'selected'
                                        : ''
                                    }}>

                                    {{ $supplier->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>
                    <div class="col-md-2">

    <label class="form-label">
        Financial Year
    </label>

    <select
        name="financial_year_id"
        class="form-select"
    >

        <option value="">
            All FY
        </option>

        @foreach($financialYears as $fy)

            <option
                value="{{ $fy->id }}"
                {{ request('financial_year_id') == $fy->id ? 'selected' : '' }}
            >
                {{ $fy->name }}
            </option>

        @endforeach

    </select>

</div>
<div class="col-md-2">

    <label class="form-label">

        Status

    </label>

    <select
        name="status"
        class="form-select"
    >

        <option value=""
            {{ request('status') == '' ? 'selected' : '' }}
        >
            Active
        </option>

        <option
            value="all"
            {{ request('status') == 'all' ? 'selected' : '' }}
        >
            All
        </option>

        <option
            value="active"
            {{ request('status') == 'active' ? 'selected' : '' }}
        >
            Active
        </option>

        <option
            value="cancelled"
            {{ request('status') == 'cancelled' ? 'selected' : '' }}
        >
            Cancelled
        </option>

    </select>

</div>
                    {{-- ACCOUNT --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Account

                        </label>

                        <select name="account_id"
                                class="form-select">

                            <option value="">

                                All Accounts

                            </option>

                            @foreach($accounts as $account)

                                <option
                                    value="{{ $account->id }}"

                                    {{
                                        request(
                                            'account_id'
                                        ) == $account->id
                                        ? 'selected'
                                        : ''
                                    }}>

                                    {{ $account->account_name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                {{-- BUTTONS --}}

                <div class="
                    d-flex
                    gap-2
                    mt-2
                ">

                    <button type="submit"
                            class="
                                btn
                                btn-primary
                            ">

                        <i class="
                            fa-solid
                            fa-filter
                        "></i>

                        Filter

                    </button>

                    <a href="{{ route(
                            'company.purchase-return-refunds.index'
                        ) }}"
                       class="
                            btn
                            btn-dark
                       ">

                        Reset

                    </a>

                </div>

            </form>

        </div>

    </div>

    {{-- SUMMARY --}}

    <div class="row mb-3">

        {{-- TOTAL REFUND --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-success
                text-white
            ">

                <div class="card-body">

                    <small>

                        Total Refund

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
                            $refunds->sum('amount'),
                            2
                        ) }}

                    </h4>

                </div>

            </div>

        </div>

        {{-- TOTAL RECORDS --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-primary
                text-white
            ">

                <div class="card-body">

                    <small>

                        Total Records

                    </small>

                    <h4 class="mb-0">

                        {{ $refunds->total() }}

                    </h4>

                </div>

            </div>

        </div>

        {{-- TODAY REFUND --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-warning
                text-dark
            ">

                <div class="card-body">

                    <small>

                        Today Refund

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
                            $refunds
                            ->where(
                                'refund_date',
                                now()->format('Y-m-d')
                            )
                            ->sum('amount'),
                            2
                        ) }}

                    </h4>

                </div>

            </div>

        </div>

    </div>

    {{-- TABLE --}}

    <div class="
        card
        border-0
        shadow-sm
    ">

        <div class="
            card-body
            table-responsive
        ">

            <table class="
                table
                table-bordered
                table-hover
                align-middle
            ">
<thead class="table-dark">

<tr>

<th>Date</th>
<th>Refund No</th>
<th>Return No</th>
<th>Supplier</th>
<th>Account</th>
<th>Amount</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>
<tbody>

@forelse($refunds as $refund)

<tr>

<td>
    {{ $refund->refund_date }}
</td>

<td>
    {{ $refund->refund_no ?? '-' }}
</td>

<td>
    {{ $refund->purchaseReturn->return_no ?? '-' }}
</td>

<td>
    {{ $refund->purchaseReturn->supplier->name ?? '-' }}
</td>

<td>
    {{ $refund->account->account_name ?? '-' }}
</td>

<td class="text-success fw-bold">
    {{ number_format($refund->amount,2) }}
</td>

<td>

@if($refund->status == 1)

<span class="badge bg-success">
    Active
</span>

@else

<span class="badge bg-danger">
    Cancelled
</span>

@endif

</td>

<td>

<div class="d-flex gap-1 flex-wrap">

<a
href="{{ route(
'company.purchase-return-refunds.show',
$refund->id
) }}"
class="btn btn-sm btn-outline-primary">

View

</a>



@if($refund->status == 1)

<form
action="{{ route(
'company.purchase-return-refunds.cancel',
$refund->id
) }}"
method="POST"
class="d-inline"
onsubmit="return confirm('Are you sure to cancel this refund?')">

@csrf

<button
type="submit"
class="btn btn-sm btn-danger">

Cancel

</button>

</form>

@endif

</div>

</td>

</tr>

@empty

<tr>

<td colspan="8"
class="text-center text-muted">

No refund found.

</td>

</tr>

@endforelse

</tbody>
            </table>

            {{-- PAGINATION --}}

            <div class="mt-3">

                {{ $refunds->links() }}

            </div>

        </div>

    </div>

</div>

@endsection