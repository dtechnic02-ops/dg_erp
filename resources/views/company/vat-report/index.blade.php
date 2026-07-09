@extends('company.layout')

@section('content')

<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">VAT Report</h4>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('company.vat-report.index') }}">
                <div class="row">

                    <div class="col-md-3">
                        <label>From Date</label>
                        <input
                            type="date"
                            name="from_date"
                            class="form-control"
                            value="{{ $fromDate }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label>To Date</label>
                        <input
                            type="date"
                            name="to_date"
                            class="form-control"
                            value="{{ $toDate }}"
                        >
                    </div>
                   
                    
                    
                    
<select name="type" class="form-control">
    <option value="">All</option>
    <option value="sale" {{ $type == 'sale' ? 'selected' : '' }}>Sale</option>
    <option value="sales_return" {{ $type == 'sales_return' ? 'selected' : '' }}>Sales Return</option>
    <option value="purchase" {{ $type == 'purchase' ? 'selected' : '' }}>Purchase</option>
    <option value="purchase_return" {{ $type == 'purchase_return' ? 'selected' : '' }}>Purchase Return</option>
</select>


                    <div class="col-md-3 d-flex align-items-end">
                        <button
                            type="submit"
                            class="btn btn-primary me-2"
                        >
                            Search
                        </button>
                    </div>

                </div>
            </form>

            <hr>

            <form
                action="{{ route('company.vat-report.print') }}"
                method="GET"
                target="_blank"
            >

                <input
                    type="hidden"
                    name="from_date"
                    value="{{ $fromDate }}"
                >

                <input
                    type="hidden"
                    name="to_date"
                    value="{{ $toDate }}"
                >

                <button
                    type="submit"
                    class="btn btn-success mb-3"
                >
                    Print Report
                </button>

            </form>

        </div>
    </div>

    <div class="row mt-3">

        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6>Sales VAT</h6>
                    <h4>{{ number_format($salesVat,2) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h6>Sales Return VAT</h6>
                    <h4>{{ number_format($salesReturnVat,2) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6>Net Output VAT</h6>
                    <h4>{{ number_format($netOutputVat,2) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-dark">
                <div class="card-body">
                    <h6>VAT Payable</h6>
                    <h4>{{ number_format($vatPayable,2) }}</h4>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-3">

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <strong>Purchase VAT</strong>
                    <h4>{{ number_format($purchaseVat,2) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <strong>Purchase Return VAT</strong>
                    <h4>{{ number_format($purchaseReturnVat,2) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <strong>Net Input VAT</strong>
                    <h4>{{ number_format($netInputVat,2) }}</h4>
                </div>
            </div>
        </div>

    </div>

    <div class="card mt-4">

        <div class="card-header">
            <h5 class="mb-0">VAT Transactions</h5>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th width="80">SN</th>
                        <th>Date</th>
                        <th>Voucher No</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th class="text-end">VAT Amount</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($transactions as $key => $row)

                        <tr>

                            <td>{{ $key + 1 }}</td>

                            <td>
                                {{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}
                            </td>

                            <td>
                                {{ $row['voucher_no'] }}
                            </td>

                            <td>
                                {{ $row['type'] }}
                            </td>

                            <td>
                                {{ $row['party'] }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['vat_amount'],2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center">
                                No Records Found
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection