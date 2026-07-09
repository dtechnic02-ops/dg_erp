@extends('company.layout')

@section('content')

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif



<div class="container-fluid">

<form action="{{ route('company.sales.store') }}"
      method="POST">

@csrf


<!-- TOP -->

<div class="card purchase-card mb-3">

    <div class="card-body">

        <div class="row g-2 align-items-end">

            <div class="col-md-2">

<label class="erp-label">
Sale Date
</label>

<input
type="date"
name="sale_date"
value="{{ now()->toDateString() }}"
class="form-control form-control-sm"
required>

</div>


{{-- 🔥 CUSTOMER --}}

<div class="col-md-5">

    <label class="erp-label">
        Customer
    </label>

    <select
        name="customer_id"
        id="customer_select"
        class="form-select form-select-sm"
        required
    >

        <option value="">
            Select Customer
        </option>

        @foreach($customers as $customer)

            <option
                value="{{ $customer->id }}"
                data-balance="{{ $customer->current_balance }}"
            >

                {{ $customer->name }}

            </option>

        @endforeach

    </select>

    <div
        id="customer_balance_box"
        class="customer-balance-box mt-2"
        style="display:none;"
    >
    </div>

</div>

<div class="col-md-3">

    <label class="erp-label">
        Sale Type
    </label>

    <select
        name="sale_type"
        id="saleType"
        class="form-select form-select-sm"
    >

        <option value="retail">
            Retail
        </option>

        <option value="wholesale">
            Wholesale
        </option>

    </select>

</div>

</div>

</div>

</div>


<!-- TABLE -->

<div class="card purchase-card mb-3">

    <div class="card-body table-responsive">

        <table class="table table-bordered purchase-table"
               id="salesTable">

            <thead>

                <tr>

                    <th style="width:28%;">
                        Product / Service
                    </th>

                    <th>
                        Qty
                    </th>

                    <th>
                        Unit
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        VAT
                    </th>

                    <th>
                        VAT Amt
                    </th>

                    <th>
                        Total
                    </th>

                    <th>
                        X
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <!-- ITEM -->

                    <td>

                        <select class="form-select form-select-sm item-select"
                                required>

                            <option value="">
                                Select Product / Service
                            </option>


                            <!-- PRODUCTS -->

                            @foreach($products as $product)

                                <option
                                    value="product-{{ $product->id }}"
                                    data-type="product"
                                    data-name="{{ $product->name }}"
                                    data-retail="{{ $product->retail_price }}"
                                    data-wholesale="{{ $product->wholesale_price }}"
                                    data-unit="{{ $product->unit->name ?? '' }}"
                                    data-vat="{{ $product->vat_id }}"
                                    data-stock="{{ $product->current_stock }}">

                                    Product :
                                    {{ $product->name }}

                                </option>

                            @endforeach


                            <!-- SERVICES -->

                            @foreach($services as $service)

                                <option
                                    value="service-{{ $service->id }}"
                                    data-type="service"
                                    data-name="{{ $service->name }}"
                                    data-retail="{{ $service->price }}"
                                    data-wholesale="{{ $service->price }}"
                                    data-unit="Service"
                                    data-vat="{{ $service->vat_id }}"
                                    data-stock="0">

                                    Service :
                                    {{ $service->name }}

                                </option>

                            @endforeach

                        </select>


                        <input type="hidden"
                               name="item_type[]"
                               class="item-type">

                        <input type="hidden"
                               name="product_id[]"
                               class="product-id">

                        <input type="hidden"
                               name="service_id[]"
                               class="service-id">

                    </td>


                    <!-- QTY -->

                    <td>

                        <input
type="number"
min="1"
name="quantity[]"
                               class="form-control form-control-sm qty">

                        <small class="stock-message text-success"></small>

                    </td>


                    <!-- UNIT -->

                    <td>

                        <input type="text"
                               class="form-control form-control-sm unit-name"
                               readonly>

                    </td>


                    <!-- PRICE -->

                    <td>

                        <input type="number"
                               step="0.01"
                               name="unit_price[]"
                               class="form-control form-control-sm price">

                    </td>


                    <!-- VAT -->

                    <td>

                        <select name="vat_id[]"
                                class="form-select form-select-sm vat-select">

                            <option value="">
                                No VAT
                            </option>

                            @foreach($vats as $vat)

                                <option value="{{ $vat->id }}"
                                        data-rate="{{ $vat->rate }}">

                                    {{ $vat->rate }}%

                                </option>

                            @endforeach

                        </select>

                        <input type="hidden"
                               name="vat_rate[]"
                               class="vat-rate">

                    </td>


                    <!-- VAT AMOUNT -->

                    <td>

                        <input type="number"
                               step="0.01"
                               name="vat_amount[]"
                               class="form-control form-control-sm vat-amount"
                               readonly>

                    </td>


                    <!-- TOTAL -->

                    <td>

                        <input type="number"
                               step="0.01"
                               name="total_price[]"
                               class="form-control form-control-sm total-price"
                               readonly>

                    </td>


                    <!-- REMOVE -->

                    <td>

                        <button type="button"
                                class="btn btn-danger btn-sm removeRow remove-btn">

                            ×

                        </button>

                    </td>

                </tr>

            </tbody>

        </table>


        <button type="button"
                class="btn btn-success btn-sm"
                id="addRow">

            + Add Item

        </button>

    </div>

</div>


<!-- TOTAL -->

<div class="card purchase-card">

    <div class="card-body">

        <div class="row">

            <div class="col-md-4">

                <button type="submit"
                        class="btn btn-primary">

                    Save Sales

                </button>

            </div>


            <div class="col-md-8">

                <div class="total-box">

               <div class="row g-2">

    {{-- PAYMENT ACCOUNT --}}

    <div class="col-md-4">

        <label class="erp-label">

            Payment Account

        </label>

      <select
    id="paymentAccount"
    name="account_id"
    class="form-select form-control-sm">

            <option value="">

                Select Account

            </option>

            @foreach($accounts as $account)

                <option
                    value="{{ $account->id }}">

                    {{ $account->account_name }}

                    (
                        {{ number_format(
                            $account->current_balance,
                            2
                        ) }}
                    )

                </option>

            @endforeach

        </select>

    </div>

                     



    {{-- SUBTOTAL --}}

    <div class="col-md-4">

        <label class="erp-label">

            Subtotal

        </label>

        <input type="number"
               name="subtotal"
               id="subtotal"
               class="form-control form-control-sm"
               readonly>

    </div>

{{-- VAT TOTAL --}}

<div class="col-md-4">

    <label class="erp-label">

        VAT Total

    </label>

    <input
        type="number"
        name="total_vat"
        id="totalVat"
        class="form-control form-control-sm"
        readonly>
</div>

    {{-- DISCOUNT --}}

    <div class="col-md-4">

        <label class="erp-label">

            Discount

        </label>

        <input type="number"
               step="0.01"
               name="discount_amount"
               id="discountAmount"
               value="0"
               class="form-control form-control-sm">

    </div>


    {{-- GRAND TOTAL --}}

    <div class="col-md-4">

        <label class="erp-label">

            Grand Total

        </label>

        <input type="number"
               name="grand_total"
               id="grandTotal"
               class="form-control form-control-sm"
               readonly>

    </div>
    

    {{-- PAID AMOUNT --}}

    <div class="col-md-4">

        <label class="erp-label">

            Paid Amount

        </label>

       <input
    type="number"
    name="paid_amount"
    id="paidAmount"
    value="0"
    class="form-control form-control-sm"
    disabled>

    </div>

    {{-- DUE AMOUNT --}}

    <div class="col-md-4">

        <label class="erp-label">

            Due Amount

        </label>

        <input type="number"
               name="due_amount"
               id="dueAmount"
               class="form-control form-control-sm"
               readonly>
            </div>
        </div>
      

      </div>

        </div>

    </div>

    </form>

</div>


<script>

class SalesForm
{
    constructor()
    {
        this.salesTable =
            document.getElementById('salesTable');

        this.customerSelect =
            document.getElementById('customer_select');

        this.customerBalanceBox =
            document.getElementById('customer_balance_box');

        this.saleType =
            document.getElementById('saleType');

        this.paymentAccount =
            document.getElementById('paymentAccount');

        this.paidAmount =
            document.getElementById('paidAmount');
    }

    init()
    {

    }
}

const salesForm =
    new SalesForm();

salesForm.init();

</script>

@endsection