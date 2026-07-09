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

            <h4 class="mb-0">

             Customer Statement

            </h4>

            <small class="text-muted">

              Customer Ledger

            </small>

        </div>

        <div>

            <a
href="#"

                <i class="
                    fa-solid
                    fa-print
                "></i>

                Print

            </a>

        </div>

    </div>

    {{-- FILTER CARD --}}

    <div class="
        card
        border-0
        shadow-sm
        mb-3
    ">

        <div class="card-body">

            <form
                method="GET"
             action="{{ route(
    'company.customer-statement.index'
) }}">

                <div class="row g-2">

                    {{-- FINANCIAL YEAR --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Financial Year

                        </label>

                        <select
                            name="financial_year_id"
                            class="form-select">

                            <option value="">

                                {{ $activeFy->name }}

                            </option>

                            <option
                                value="all"
                                {{
                                    request(
                                        'financial_year_id'
                                    ) == 'all'
                                    ? 'selected'
                                    : ''
                                }}>

                                All

                            </option>

                         @foreach($financialYears as $fy)

                                <option
                                    value="{{ $fy->id }}"
                                    {{
                                        request(
                                            'financial_year_id'
                                        ) == $fy->id
                                        ? 'selected'
                                        : ''
                                    }}>

                                    {{ $fy->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- SUPPLIER --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Customer

                        </label>

                        <select
                           name="customer_id"
                            class="form-select">

                            <option value="">

                                All Customers

                            </option>

                       @foreach($customers as $customer)
                                <option
                                    value="{{ $customer->id }}"
                                    {{
                                       request('customer_id')== $customer->id
                                        ? 'selected'
                                        : ''
                                    }}>

                                    {{ $customer->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- STATUS --}}

                    <div class="col-md-1">

                        <label class="form-label">

                            Status

                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option value="">

                                Active

                            </option>

                            <option
                                value="all">

                                All

                            </option>

                            <option
                                value="1"
                                {{
                                    request(
                                        'status'
                                    ) == '1'
                                    ? 'selected'
                                    : ''
                                }}>

                                Active

                            </option>

                            <option
                                value="0"
                                {{
                                    request(
                                        'status'
                                    ) == '0'
                                    ? 'selected'
                                    : ''
                                }}>

                                Cancelled

                            </option>

                        </select>

                    </div>

                    {{-- FROM DATE --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            From

                        </label>

                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            value="{{ $startDate }}">

                    </div>

                    {{-- TO DATE --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            To

                        </label>

                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="{{ $endDate }}">

                    </div>

                    {{-- SEARCH --}}

                    <div class="col-md-2">

                        <label class="form-label">

                            Search

                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Voucher / Description">

                    </div>

                    {{-- BUTTONS --}}

                    <div class="
                        col-md-1
                        d-grid
                    ">

                        <label
                            class="
                                form-label
                                text-white
                            ">

                            .

                        </label>

                        <button
                            class="
                                btn
                                btn-primary
                            ">

                            Filter

                        </button>

                    </div>

                </div>

                <div class="
                    mt-2
                    d-flex
                    gap-2
                ">

                    <a
                     href="{{ route(
    'company.customer-statement.index'
) }}"
                        class="
                            btn
                            btn-light
                            border
                        ">

                        Reset

                    </a>

                </div>

            </form>

        </div>

    </div>
    {{-- SUMMARY --}}

    <div class="row g-2 mb-3">

        {{-- OPENING BALANCE --}}

        <div class="col-md-3">

            <div class="
                card
                border-0
                shadow-sm
            ">

                <div class="
                    card-body
                    py-2
                ">

                    <small class="text-muted">

                        Opening Balance

                    </small>

                    <h5 class="
                        mb-0
                        text-primary
                    ">

                        {{ number_format(
                            $openingBalance,
                            2
                        ) }}

                    </h5>

                </div>

            </div>

        </div>

        {{-- TOTAL DEBIT --}}

        <div class="col-md-3">

            <div class="
                card
                border-0
                shadow-sm
            ">

                <div class="
                    card-body
                    py-2
                ">

                    <small class="text-muted">

                        Total Debit

                    </small>

                    <h5 class="
                        mb-0
                        text-success
                    ">

                        {{ number_format(
                            $totalDebit,
                            2
                        ) }}

                    </h5>

                </div>

            </div>

        </div>

        {{-- TOTAL CREDIT --}}

        <div class="col-md-3">

            <div class="
                card
                border-0
                shadow-sm
            ">

                <div class="
                    card-body
                    py-2
                ">

                    <small class="text-muted">

                        Total Credit

                    </small>

                    <h5 class="
                        mb-0
                        text-danger
                    ">

                        {{ number_format(
                            $totalCredit,
                            2
                        ) }}

                    </h5>

                </div>

            </div>

        </div>

        {{-- CLOSING BALANCE --}}

        <div class="col-md-3">

            <div class="
                card
                border-0
                shadow-sm
            ">

                <div class="
                    card-body
                    py-2
                ">

                    <small class="text-muted">

                        Closing Balance

                    </small>

                    <h5 class="
                        mb-0
                        text-warning
                    ">

                        {{ number_format(
                            $closingBalance,
                            2
                        ) }}

                    </h5>

                </div>

            </div>

        </div>

    </div>

    {{-- RECORD INFO --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mb-2
    ">

        <small class="text-muted">

            Showing

            <strong>

                {{ $transactions->firstItem() ?? 0 }}

            </strong>

            -

            <strong>

                {{ $transactions->lastItem() ?? 0 }}

            </strong>

            of

            <strong>

                {{ $transactions->total() }}

            </strong>

            records

        </small>

    </div>
        {{-- LEDGER TABLE --}}

    <div class="
        card
        border-0
        shadow-sm
    ">

        <div class="table-responsive">

            <table class="
                table
                table-hover
                table-bordered
                align-middle
                mb-0
            ">

                <thead class="table-light">

                    <tr>

                        <th width="60">

                            #

                        </th>

                        <th width="110">

                            Date

                        </th>

                        <th width="150">

                            Voucher

                        </th>

                        <th width="140">

                            Type

                        </th>

                       @if(!request()->filled('customer_id'))

                        <th>

                            Customer

                        </th>

                        @endif

                        <th>

                            Description

                        </th>

                        <th class="text-end">

                            Debit

                        </th>

                        <th class="text-end">

                            Credit

                        </th>

                        <th class="text-end">

                            Balance

                        </th>

                        <th width="90">

                            Status

                        </th>

                    </tr>

                </thead>

                <tbody>
@forelse($transactions as $transaction)

                        <tr
                            class="{{ $transaction->status == 0 ? 'table-danger' : '' }}">

                            <td>

                                {{ $transactions->firstItem() + $loop->index }}

                            </td>

                            <td>

                                {{ \Carbon\Carbon::parse(
                                    $transaction->transaction_date
                                )->format('d-M-Y') }}

                            </td>

                            <td>

<a
    href="{{
        \App\Services\VoucherRouteService::url(

            $transaction->reference_type,

            $transaction->reference_id

        )
    }}"
    target="_blank"
    class="
        text-decoration-none
        fw-semibold
    ">

    {{ $transaction->voucher_no }}

</a>


                            </td>

                            <td>

                                {{ ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $transaction->reference_type
                                    )
                                ) }}

                            </td>

                           @if(!request()->filled('customer_id'))

                            <td>

                                {{ $transaction->customer->name ?? '-' }}

                            </td>

                            @endif

                            <td>

                                {{ $transaction->description }}

                            </td>

                            <td class="
                                text-end
                                fw-bold
                            ">

                                @if($transaction->debit > 0)

                                    {{ number_format(
                                        $transaction->debit,
                                        2
                                    ) }}

                                @else

                                    -

                                @endif

                            </td>

                            <td class="
                                text-end
                                fw-bold
                            ">

                                @if($transaction->credit > 0)

                                    {{ number_format(
                                        $transaction->credit,
                                        2
                                    ) }}

                                @else

                                    -

                                @endif

                            </td>

                            <td class="
                                text-end
                                fw-bold
                            ">

                                {{ number_format(
                                    $transaction->balance,
                                    2
                                ) }}

                            </td>

                            <td>

                               @if($transaction->status)

                                    <span class="
                                        badge
                                        rounded-pill
                                        bg-success
                                    ">

                                        Active

                                    </span>

                                @else

                                    <span class="
                                        badge
                                        rounded-pill
                                        bg-danger
                                    ">

                                        Cancelled

                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                               colspan="{{ request()->filled('customer_id') ? 9 : 10 }}"
                                class="
                                    text-center
                                    py-5
                                    text-muted
                                ">

                                No customer transactions found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>
        {{-- FOOTER --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mt-3
    ">

        <div>

            <small class="text-muted">

                Showing

                {{ $transactions->firstItem() ?? 0 }}

                to

                {{ $transactions->lastItem() ?? 0 }}

                of

                {{ $transactions->total() }}

                entries

            </small>

        </div>

        <div>

            {{ $transactions->links() }}

        </div>

    </div>

</div>

@endsection
    