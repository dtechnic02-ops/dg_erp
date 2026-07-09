@extends('company.layout')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h4 class="mb-0">
            Purchase Payments
        </h4>

        <small class="text-muted">
            Manage supplier payment records
        </small>

    </div>

    <a
        href="{{ route(
            'company.purchase-payments.print',
            request()->query()
        ) }}"
        target="_blank"
        class="btn btn-success"
    >
        <i class="fa fa-print"></i>
        Print
    </a>


</div>
<div class="container-fluid">

<div class="card shadow-sm border-0 mb-3">

    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                <div class="col-md-3">

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
                <div class="col-md-2">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        value="{{ request('start_date',$startDate) }}"
                        class="form-control"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        value="{{ request('end_date',$endDate) }}"
                        class="form-control"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label d-block">
                        &nbsp;
                    </label>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

<tr>

    <th>#</th>

    <th>Date</th>

    <th>Payment No</th>

    <th>Supplier</th>

    <th>Invoice No</th>

    <th>Account</th>

    <th class="text-end">
        Amount
    </th>

    <th>
        Status
    </th>

    <th width="180">
        Action
    </th>

</tr>

</thead>

                <tbody>

                    @php
                        $totalAmount = 0;
                    @endphp

                    @forelse($payments as $payment)

                        @php
                            $totalAmount += $payment->amount;
                        @endphp

               <tr>

    <td>
        {{ $loop->iteration }}
    </td>

    <td>
        {{ $payment->payment_date }}
    </td>

    <td>
        {{ $payment->payment_no }}
    </td>

    <td>
        {{ $payment->supplier->name ?? '-' }}
    </td>

    <td>
        {{ $payment->invoice->invoice_no ?? '-' }}
    </td>

    <td>
        {{ $payment->account->account_name ?? '-' }}
    </td>

    <td class="text-end">
        {{ number_format($payment->amount,2) }}
    </td>

    <td>

        @if($payment->status == 1)

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

        <a
            href="{{ route(
                'company.purchase-payments.show',
                $payment->id
            ) }}"
            class="btn btn-sm btn-info"
        >
            Show
        </a>

        @if($payment->status == 1)

            <a
                href="{{ route(
                    'company.purchase-payments.edit',
                    $payment->id
                ) }}"
                class="btn btn-sm btn-warning"
            >
                Edit
            </a>

            <form
                action="{{ route(
                    'company.purchase-payments.cancel',
                    $payment->id
                ) }}"
                method="POST"
                class="d-inline"
                onsubmit="return confirm('Are you sure to cancel this payment?')"
            >

                @csrf

                <button
                    type="submit"
                    class="btn btn-sm btn-danger"
                >
                    Cancel
                </button>

            </form>

        @endif

    </td>

</tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center">

                                No Payment Found

                            </td>

                        </tr>

                    @endforelse

                </tbody>

               <tfoot>

<tr>

    <th colspan="6" class="text-end">
        Total
    </th>

    <th class="text-end">
        {{ number_format($totalAmount,2) }}
    </th>

    <th></th>

    <th></th>

</tr>

</tfoot>

            </table>

        </div>

        <div class="mt-3">

            {{ $payments->links() }}

        </div>

    </div>

</div>


</div>

@endsection
