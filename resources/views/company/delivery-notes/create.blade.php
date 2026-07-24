@extends('company.layout')

@section('title', 'Create Delivery Note')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Create Delivery Note</h1>
                    <p class="text-muted small mb-0">Operational delivery only — quantity tracking, no financial posting</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('company.delivery-notes.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            @if ($activeFy)
                <div class="alert alert-info dg-alert" role="alert">
                    Active Financial Year: <strong>{{ $activeFy->name }}</strong>
                    ({{ \Illuminate\Support\Carbon::parse($activeFy->start_date)->format('d-m-Y') }} to {{ \Illuminate\Support\Carbon::parse($activeFy->end_date)->format('d-m-Y') }})
                </div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Select Customer &amp; Invoice</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.delivery-notes.create') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="load_customer_id" class="form-label">Customer *</label>
                                <select name="customer_id" id="load_customer_id" class="form-select dg-select" required onchange="this.form.submit()">
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id', request('customer_id')) == $customer->id)>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="load_sales_invoice_id" class="form-label">Sales Invoice *</label>
                                <select name="sales_invoice_id" id="load_sales_invoice_id" class="form-select dg-select" @disabled(!request('customer_id')) onchange="this.form.submit()">
                                    <option value="">Select Invoice</option>
                                    @foreach ($salesInvoices as $invoice)
                                        <option value="{{ $invoice->id }}" @selected(old('sales_invoice_id', request('sales_invoice_id')) == $invoice->id)>
                                            {{ $invoice->invoice_no }} — {{ $invoice->sale_date?->format('d-m-Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <a href="{{ route('company.delivery-notes.create') }}" class="btn btn-outline-secondary dg-btn">Reset Selection</a>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            @if ($selectedInvoice && count($lineItems))
                <form method="POST" action="{{ route('company.delivery-notes.store') }}">
                    @csrf

                    <input type="hidden" name="customer_id" value="{{ old('customer_id', request('customer_id')) }}">
                    <input type="hidden" name="sales_invoice_id" value="{{ old('sales_invoice_id', request('sales_invoice_id')) }}">

                    <section class="dg-section">
                        <article class="card dg-card">
                            <header class="card-header dg-card-header">
                                <h2 class="h6 mb-0">Delivery Details</h2>
                            </header>
                            <div class="card-body dg-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Sales Invoice</label>
                                        <input type="text" class="form-control dg-input" value="{{ $selectedInvoice->invoice_no }}" readonly>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="employee_id" class="form-label">Delivery Employee *</label>
                                        <select name="employee_id" id="employee_id" class="form-select dg-select" required>
                                            <option value="">Select Employee</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                                    {{ $employee->employee_code }} — {{ $employee->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="delivery_date" class="form-label">Delivery Date *</label>
                                        <input type="date" name="delivery_date" id="delivery_date" class="form-control dg-input" value="{{ old('delivery_date', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="col-12">
                                        <label for="remarks" class="form-label">Remarks</label>
                                        <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="dg-section">
                        <article class="card dg-card">
                            <header class="card-header dg-card-header">
                                <h2 class="h6 mb-0">Delivery Items</h2>
                            </header>
                            <div class="card-body dg-card-body">
                                <div class="table-responsive">
                                    <table class="table dg-table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th class="text-end">Invoice Qty</th>
                                                <th class="text-end">Previously Delivered</th>
                                                <th class="text-end">Remaining Qty</th>
                                                <th class="text-end" width="160">Planned Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($lineItems as $index => $line)
                                                <tr>
                                                    <td>{{ $line['item_name'] }}</td>
                                                    <td>{{ ucfirst($line['item_type']) }}</td>
                                                    <td class="text-end">{{ number_format($line['invoice_qty'], 2) }}</td>
                                                    <td class="text-end">{{ number_format($line['previously_delivered_qty'], 2) }}</td>
                                                    <td class="text-end">{{ number_format($line['remaining_qty'], 2) }}</td>
                                                    <td class="text-end">
                                                        <input type="hidden" name="items[{{ $index }}][sales_item_id]" value="{{ $line['sales_item_id'] }}">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            max="{{ $line['remaining_qty'] }}"
                                                            name="items[{{ $index }}][planned_qty]"
                                                            class="form-control dg-input text-end"
                                                            value="{{ old('items.' . $index . '.planned_qty', 0) }}"
                                                        >
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <footer class="card-footer dg-card-footer">
                                <button type="submit" class="btn btn-primary dg-btn">Create Delivery (Ready)</button>
                            </footer>
                        </article>
                    </section>
                </form>
            @elseif (request('sales_invoice_id'))
                <div class="alert alert-warning dg-alert" role="alert">
                    No deliverable items remain for this invoice.
                </div>
            @elseif (request('customer_id'))
                <div class="alert alert-info dg-alert" role="alert">
                    Select a sales invoice to load delivery items.
                </div>
            @else
                <div class="alert alert-info dg-alert" role="alert">
                    Select a customer to begin creating a delivery note.
                </div>
            @endif

        </div>
    </main>
</div>

@endsection
