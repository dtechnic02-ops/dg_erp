@extends('company.layout')

@section('title', 'Sales Invoice')

@push('styles')

<link rel="stylesheet"
href="{{ asset('assets/company/css/common.css') }}">

@endpush

@section('content')

<form
id="salesForm"
method="POST"
action="{{ route('company.sales.store') }}">

@csrf

<div class="container-fluid">

<!-- ==========================================
TOP TOOLBAR
========================================== -->

<div class="card mb-3">

<div class="card-body py-2">

<div class="d-flex flex-wrap gap-2">

<a
href="{{ route('company.dashboard') }}"
class="btn btn-outline-primary btn-sm">

Dashboard

</a>

<a
href="{{ route('company.sales.index') }}"
class="btn btn-outline-secondary btn-sm">

Sales List

</a>

<button
type="button"
onclick="location.reload()"
class="btn btn-outline-success btn-sm">

Refresh

</button>

<a
href="{{ route('company.customers.index') }}"
class="btn btn-outline-dark btn-sm">

Customer

</a>

<a
href="{{ route('company.products.index') }}"
class="btn btn-outline-dark btn-sm">

Product

</a>

<a
href="{{ route('company.services.index') }}"
class="btn btn-outline-dark btn-sm">

Service

</a>

<a
href="{{ route('company.units.index') }}"
class="btn btn-outline-dark btn-sm">

Unit

</a>

<a
href="{{ route('company.categories.index') }}"
class="btn btn-outline-dark btn-sm">

Category

</a>

</div>

</div>

</div>

<!-- ==========================================
SALES INFORMATION
========================================== -->

<div class="card mb-3">

<div class="card-header">

<h5 class="mb-0">

Sales Information

</h5>

</div>

<div class="card-body">

<div class="row g-3">

<div class="col-xl-3 col-lg-4 col-md-6">

<label class="form-label">

Customer

</label>

<select
name="customer_id"
id="customer_id"
class="form-select">

<option value="">

Select Customer

</option>

@foreach($customers as $customer)

<option
value="{{ $customer->id }}"
data-mobile="{{ $customer->mobile }}"
data-balance="{{ $customer->balance }}">

{{ $customer->name }}

</option>

@endforeach

</select>

<small
id="customer_balance"
class="text-primary">

Balance : 0.00

</small>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<label class="form-label">

Sale Date

</label>

<input
type="date"
name="sale_date"
class="form-control"
value="{{ date('Y-m-d') }}">

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<label class="form-label">

Invoice No

</label>

<input
type="text"
class="form-control"
value="{{ $invoiceNo }}"
readonly>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<label class="form-label">

Price Type

</label>

<select
id="price_type"
name="price_type"
class="form-select">

<option value="retail">

Retail

</option>

<option value="wholesale">

Wholesale

</option>

</select>

</div>

<div class="col-xl-3 col-lg-4 col-md-6">

<label class="form-label">

Payment Account

</label>

<select
id="account_id"
name="account_id"
class="form-select">

<option value="">

Select Account

</option>

@foreach($accounts as $account)

<option
value="{{ $account->id }}"
data-balance="{{ $account->current_balance }}">

{{ $account->account_name }}

</option>

@endforeach

</select>

<small
id="account_balance"
class="text-success">

Balance : 0.00

</small>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<label class="form-label">

Paid Amount

</label>

<input
type="number"
id="paid_amount"
name="paid_amount"
class="form-control"
value="0">

</div>

<div class="col-xl-3 col-lg-4 col-md-6">

<label class="form-label">

Reference No

</label>

<input
type="text"
name="reference_no"
class="form-control">

</div>

<div class="col-xl-4 col-lg-12">

<label class="form-label">

Customer Mobile

</label>

<input
type="text"
id="customer_mobile"
class="form-control"
readonly>

</div>

</div>

</div>

</div>
<!-- ==========================================
SALES ITEMS
========================================== -->

<div class="card mb-3">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">

            Sales Items

        </h5>

        <button
            type="button"
            id="addRow"
            class="btn btn-primary btn-sm">

            + Add Item

        </button>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                id="salesTable"
                class="table table-bordered table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th width="50">#</th>

                        <th width="120">

                            Type

                        </th>

                        <th>

                            Product / Service

                        </th>

                        <th width="80">

                            Qty

                        </th>

                        <th width="90">

                            Unit

                        </th>

                        <th width="120">

                            Price

                        </th>

                        <th width="120">

                            VAT

                        </th>

                        <th width="120">

                            VAT Amount

                        </th>

                        <th width="140">

                            Total

                        </th>

                        <th width="70">

                            Delete

                        </th>

                    </tr>

                </thead>

                <tbody id="salesBody">

                    <tr class="sales-row">

                        <td class="row-number text-center">

                            1

                        </td>

                        <td>

                            <select
                                name="item_type[]"
                                class="form-select item-type">

                                <option value="product">

                                    Product

                                </option>

                                <option value="service">

                                    Service

                                </option>

                            </select>

                        </td>

                        <td>

                            <select
                                name="product_id[]"
                                class="form-select product-select">

                                <option value="">

                                    Select Product

                                </option>

                                @foreach($products as $product)

                                <option
                                    value="{{ $product->id }}"
                                    data-retail="{{ $product->retail_price }}"
                                    data-wholesale="{{ $product->wholesale_price }}"
                                    data-stock="{{ $product->current_stock }}"
                                    data-unit="{{ $product->unit->name ?? '' }}"
                                    data-unit-id="{{ $product->unit_id }}"
                                    data-vat-id="{{ $product->vat_id }}"
                                    data-vat-rate="{{ $product->vat_rate ?? 0 }}">

                                    {{ $product->name }}

                                </option>

                                @endforeach

                            </select>

                            <select
                                name="service_id[]"
                                class="form-select service-select d-none mt-2">

                                <option value="">

                                    Select Service

                                </option>

                                @foreach($services as $service)

                                <option
                                    value="{{ $service->id }}"
                                    data-price="{{ $service->price }}"
                                    data-vat="{{ $service->vat_rate ?? 0 }}">

                                    {{ $service->name }}

                                </option>

                                @endforeach

                            </select>

                            <small
                                class="text-primary product-stock">

                                Stock : 0

                            </small>

                        </td>

                        <td>

                            <input
                                type="number"
                                name="quantity[]"
                                class="form-control quantity text-center"
                                value="1"
                                min="1">

                        </td>

                        <td>

                            <input
                                type="text"
                                name="unit_name[]"
                                class="form-control unit-name text-center"
                                readonly>

                            <input
                                type="hidden"
                                name="unit_id[]"
                                class="unit-id">

                        </td>

                        <td>

                            <input
                                type="number"
                                name="unit_price[]"
                                class="form-control unit-price text-end"
                                value="0"
                                step="0.01">

                        </td>

                        <td>

                            <select
                                name="vat_id[]"
                                class="form-select vat-select">

                                <option value="">

                                    Select VAT

                                </option>

                                @foreach($vats as $vat)

                                <option
                                    value="{{ $vat->id }}"
                                    data-rate="{{ $vat->rate }}">

                                    {{ $vat->name }}
                                    ({{ $vat->rate }}%)

                                </option>

                                @endforeach

                            </select>

                            <input
                                type="hidden"
                                name="vat_rate[]"
                                class="vat-rate"
                                value="0">

                        </td>

                        <td>

                            <input
                                type="text"
                                name="vat_amount[]"
                                class="form-control vat-amount text-end"
                                value="0.00"
                                readonly>

                        </td>

                        <td>

                            <input
                                type="text"
                                name="total_price[]"
                                class="form-control total-price text-end fw-bold"
                                value="0.00"
                                readonly>

                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm remove-row">

                                🗑

                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

<input
type="hidden"
id="subtotal"
name="subtotal"
value="0">

<input
type="hidden"
id="total_vat"
name="total_vat"
value="0">

<input
type="hidden"
id="grand_total"
name="grand_total"
value="0">
<!-- ==========================================
NOTE & SUMMARY
========================================== -->

<div class="row g-3 mb-3">

    <!-- Invoice Note -->

    <div class="col-xl-8 col-lg-7">

        <div class="card h-100">

            <div class="card-header">

                <h5 class="mb-0">

                    Invoice Note

                </h5>

            </div>

            <div class="card-body">

                <textarea
                    name="note"
                    rows="7"
                    class="form-control"
                    placeholder="Write invoice note..."></textarea>

            </div>

        </div>

    </div>

    <!-- Invoice Summary -->

    <div class="col-xl-4 col-lg-5">

        <div class="card h-100">

            <div class="card-header">

                <h5 class="mb-0">

                    Invoice Summary

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-sm mb-0">

                    <tr>

                        <th>

                            Subtotal

                        </th>

                        <td>

                            <input
                                id="subtotal_display"
                                class="form-control text-end"
                                value="0.00"
                                readonly>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Discount

                        </th>

                        <td>

                            <input
                                id="discount_amount"
                                name="discount_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value="0"
                                class="form-control text-end">

                        </td>

                    </tr>

                    <tr>

                        <th>

                            VAT

                        </th>

                        <td>

                            <input
                                id="vat_display"
                                class="form-control text-end"
                                value="0.00"
                                readonly>

                        </td>

                    </tr>

                    <tr class="table-light">

                        <th>

                            Grand Total

                        </th>

                        <td>

                            <input
                                id="grand_total_display"
                                class="form-control text-end fw-bold"
                                value="0.00"
                                readonly>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Paid

                        </th>

                        <td>

                            <input
                                id="paid_display"
                                class="form-control text-end"
                                value="0.00"
                                readonly>

                        </td>

                    </tr>

                    <tr class="table-warning">

                        <th>

                            Due

                        </th>

                        <td>

                            <input
                                id="due_display"
                                class="form-control text-end fw-bold text-danger"
                                value="0.00"
                                readonly>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- ==========================================
ACTION BUTTONS
========================================== -->

<div class="card">

    <div class="card-body">

        <div
            class="d-flex justify-content-between flex-wrap gap-2">

            <button
                type="reset"
                class="btn btn-warning">

                Reset

            </button>

            <div>

                <a
                    href="{{ route('company.sales.index') }}"
                    class="btn btn-secondary">

                    Cancel

                </a>

                <button
                    id="saveInvoice"
                    type="submit"
                    class="btn btn-primary">

                    Save Sales Invoice

                </button>

            </div>

        </div>

    </div>

</div>

</div>

</form>

@endsection


@push('scripts')

<script
src="{{ asset('assets/company/js/dg.js') }}">
</script>

@endpush