@extends('company.layout')

@section('content')


<div class="container-fluid">

    {{-- TOP HEADER --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mb-3
    ">
  
        <div>

            <h4 class="mb-0">

                Purchase List

            </h4>

            <small class="text-muted">

                Last 1 year purchase data

            </small>

        </div>
<a href="{{ route('company.purchases.create') }}"
   class="btn btn-primary">
    <i class="fa fa-plus"></i>
    New Purchase
</a>

<a href="{{ route('company.purchases.print', request()->query()) }}"
   target="_blank"
   class="btn btn-success">
    <i class="fa fa-print"></i>
    Print
</a>
</div>
       

    

    {{-- FILTER --}}

  {{-- FILTER --}}
<div class="card border-0 shadow-sm mb-2">

    <div class="card-body">

        <form method="GET"
              action="{{ route('company.purchases.index') }}">

            <div class="row g-3">

                <div class="col-md-3">

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
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        class="form-select"
                    >

                        <option value="">
                            All Suppliers
                        </option>

                        @foreach($suppliers as $supplier)

                            <option
                                value="{{ $supplier->id }}"
                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->name }}
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

        <option value="">
            Active
        </option>

        <option
            value="all"
            {{ request('status') == 'all' ? 'selected' : '' }}
        >
            All
        </option>

        <option
            value="cancelled"
            {{ request('status') == 'cancelled' ? 'selected' : '' }}
        >
            Cancelled
        </option>

    </select>

</div>
                <div class="col-md-2">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ $startDate }}"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ $endDate }}"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label d-block">
                        &nbsp;
                    </label>

                    <button
                        class="btn btn-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('company.purchases.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>
    {{-- SUMMARY --}}

    <div class="row mb-3">

        {{-- GRAND TOTAL --}}
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

                        Grand Total

                    </small>

                    <h4 class="mb-0">

                        {{ number_format($totalGrandTotal,2) }}

                    </h4>

                </div>

            </div>

        </div>
<div class="col-md-2">

    <div class="
        card
        border-0
        shadow-sm
        bg-info
        text-white
    ">

        <div class="card-body">

            <small>
                Total Records
            </small>

            <h4 class="mb-0">
                {{ $totalRecords }}
            </h4>

        </div>

    </div>

</div>
<div class="col-md-2">

    <div class="
        card
        border-0
        shadow-sm
        bg-secondary
        text-white
    ">

        <div class="card-body">

            <small>
                Cancelled
            </small>

            <h4 class="mb-0">
                {{ $totalCancelled }}
            </h4>

        </div>

    </div>

</div>
        {{-- PAID --}}

        <div class="col-md-2">

            <div class="
                card
                border-0
                shadow-sm
                bg-success
                text-white
            ">

                <div class="card-body">

                    <small>

                        Paid Amount

                    </small>

                    <h4 class="mb-0">

                     {{ number_format(
    $totalPaidAmount,
    2
) }}

                    </h4>

                </div>

            </div>

        </div>

        {{-- DUE --}}

        <div class="col-md-2">

            <div class="
                card
                border-0
                shadow-sm
                bg-danger
                text-white
            ">

                <div class="card-body">

                    <small>

                        Due Amount

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
    $totalDueAmount,
    2
) }}

                    </h4>

                </div>

            </div>

        </div>

    </div>

    {{-- TABLE --}}

    <div class="card shadow-sm border-0">

        <div class="card-body table-responsive">

            <table class="
                table
                table-bordered
                table-hover
                align-middle
            ">

                <thead class="table-dark">

                    <tr>

                        <th>

                            Invoice

                        </th>

                        <th>

                            Date

                        </th>

                        <th>

                            Supplier

                        </th>

                        <th>

                            Grand Total

                        </th>

                        <th>

                            Paid

                        </th>

                        <th>

                            Due

                        </th>

                        <th>

                            Status

                        </th>

                        <th width="200">

                            Action

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($invoices as $invoice)

                        <tr>

                            {{-- INVOICE --}}

                            <td>

                                {{ $invoice->invoice_no }}

                            </td>

                            {{-- DATE --}}

                            <td>

                                {{ $invoice->purchase_date }}

                            </td>

                            {{-- SUPPLIER --}}

                            <td>

                                {{ $invoice->supplier->name ?? '-' }}

                            </td>

                            {{-- GRAND TOTAL --}}

                            <td>

                                {{ number_format(
                                    $invoice->grand_total,
                                    2
                                ) }}

                            </td>

                            {{-- PAID --}}

                            <td class="text-success fw-bold">

                                {{ number_format(
                                    $invoice->paid_amount,
                                    2
                                ) }}

                            </td>

                            {{-- DUE --}}

                            <td class="text-danger fw-bold">

                                {{ number_format(
                                    $invoice->due_amount,
                                    2
                                ) }}

                            </td>

                            {{-- STATUS --}}

                            <td>

                                @if(
                                    $invoice->payment_status
                                    == 'paid'
                                )

                                    <span class="
                                        badge
                                        bg-success
                                    ">

                                        Paid

                                    </span>

                                @elseif(
                                    $invoice->payment_status
                                    == 'partial'
                                )

                                    <span class="
                                        badge
                                        bg-warning
                                    ">

                                        Partial

                                    </span>

                                @else

                                    <span class="
                                        badge
                                        bg-danger
                                    ">

                                        Unpaid

                                    </span>

                                @endif

                            </td>

                            {{-- ACTION --}}

                          <td>

    <div class="
        d-flex
        gap-1
        flex-wrap
    ">

        {{-- VIEW --}}

        <a href="{{ route(
                'company.purchases.show',
                $invoice->id
            ) }}"
           class="
                btn
                btn-sm
                btn-outline-primary
            ">

            <i class="fa-solid fa-eye"></i>

            View

        </a>

        {{-- PAYMENT --}}
        {{-- PAYMENT --}}

@if($invoice->due_amount > 0)

<a href="{{ route(
'company.purchase-payments.create',
$invoice->id
) }}"
class="
btn
btn-sm
btn-outline-success
">

<i class="fa-solid fa-money-bill"></i>

Payment

</a>

@endif

       
@if($invoice->grand_total > 0)

<a href="{{ route(
'company.purchase-return.create',
$invoice->id
) }}"
class="
btn
btn-sm
btn-outline-danger
">

<i class="fa-solid fa-rotate-left"></i>

Return

</a>
@if($invoice->status == 1)

<form
    action="{{ route(
        'company.purchases.cancel',
        $invoice->id
    ) }}"
    method="POST"
>
    @csrf

    <button
        type="submit"
        class="btn btn-danger btn-sm"
    >
        Cancel
    </button>
</form>

@endif

@endif

</div>

</td>

</tr>

                    @empty

                        <tr>

                            <td colspan="8"
                                class="text-center text-muted">

                                No purchase data found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

            {{-- PAGINATION --}}

            <div class="mt-3">

                {{ $invoices->links() }}

            </div>

        </div>

    </div>

</div>

@endsection