@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- PAGE HEADER --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mb-4
    ">

        <div>

            <h4 class="mb-1">

                Purchase Return

            </h4>

            <small class="text-muted">

                Invoice :
                {{ $invoice->invoice_no }}

            </small>

        </div>

        {{-- BACK --}}

        <a href="{{ route(
                'company.purchases.show',
                $invoice->id
            ) }}"
           class="
                btn
                btn-dark
            ">

            <i class="
                fa-solid
                fa-arrow-left
            "></i>

            Back

        </a>

    </div>

    {{-- ALERTS --}}

    @if(session('success'))

        <div class="
            alert
            alert-success
        ">

            {{ session('success') }}

        </div>

    @endif

    @if($errors->any())

        <div class="
            alert
            alert-danger
        ">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>

                        {{ $error }}

                    </li>

                @endforeach

            </ul>

        </div>

    @endif

    {{-- FORM --}}

    <form action="{{ route(
            'company.purchase-return.store'
        ) }}"
        method="POST">

        @csrf

        <input type="hidden"
               name="purchase_invoice_id"
               value="{{ $invoice->id }}">

        <div class="
            card
            border-0
            shadow-sm
        ">

            <div class="card-body">

                {{-- HEADER --}}

                <div class="row mb-4">

                    {{-- SUPPLIER --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Supplier

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ $invoice->supplier->name }}"
                               readonly>

                    </div>

                    {{-- DATE --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Return Date

                        </label>

                        <input type="date"
                               name="return_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>

                    </div>

                    {{-- NOTE --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Note

                        </label>

                        <input type="text"
                               name="note"
                               class="form-control">

                    </div>

                </div>

                {{-- INFO BOXES --}}

                <div class="row mb-4">

                    {{-- DUE --}}

                    <div class="col-md-4">

                        <div class="
                            alert
                            alert-danger
                            mb-0
                        ">

                            <strong>

                                Supplier Due:

                            </strong>

                            {{ number_format(
                                $invoice->due_amount,
                                2
                            ) }}

                        </div>

                    </div>

                    {{-- PAID --}}

                    <div class="col-md-4">

                        <div class="
                            alert
                            alert-success
                            mb-0
                        ">

                            <strong>

                                Paid Amount:

                            </strong>

                            {{ number_format(
                                $invoice->paid_amount,
                                2
                            ) }}

                        </div>

                    </div>

                    {{-- REFUND NOTE --}}

                    <div class="col-md-4">

                        <div class="
                            alert
                            alert-warning
                            mb-0
                        ">

                            <strong>

                                Refund Pending

                            </strong>

                            <br>

                            Supplier refund
                            payment will be
                            received separately.

                        </div>

                    </div>

                </div>

                {{-- TABLE --}}

                <div class="table-responsive">

                    <table class="
                        table
                        table-bordered
                        table-hover
                        align-middle
                    ">

                        <thead class="table-dark">

                            <tr>

                                <th width="25%">

                                    Product

                                </th>

                                <th width="10%">

                                    Purchased

                                </th>

                                <th width="10%">

                                    Stock

                                </th>

                                <th width="15%">

                                    Return Qty

                                </th>

                                <th width="10%">

                                    Price

                                </th>

                                <th width="10%">

                                    VAT %

                                </th>

                                <th width="10%">

                                    VAT Amt

                                </th>

                                <th width="10%">

                                    Total

                                </th>

                            </tr>

                        </thead>

                        <tbody>

@foreach($invoice->items as $key => $item)

@php

    $product =
        $item->product;

    /**
     * 🔥 ALREADY RETURNED
     */

    $alreadyReturned =
        \App\Models\PurchaseReturnItem::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'product_id',
            $product->id
        )
        ->whereHas(
            'purchaseReturn',
            function ($q) use ($invoice) {

                $q->where(
                    'purchase_invoice_id',
                    $invoice->id
                );
            }
        )
        ->sum('quantity');

    /**
     * 🔥 REMAINING
     */

    $remainingQty =
        $item->quantity
        - $alreadyReturned;

@endphp

<tr>

    {{-- PRODUCT --}}

    <td>

        <div class="fw-bold">

            {{ $product->name }}

        </div>

        <small class="text-muted">

            {{ $product->barcode }}

        </small>

        <input type="hidden"
               name="product_id[]"
               value="{{ $product->id }}">

    </td>

    {{-- PURCHASED --}}

    <td>

        {{ $item->quantity }}

    </td>

    {{-- STOCK --}}

    <td>

        <span class="
            badge
            bg-success
        ">

            {{ $product->current_stock }}

        </span>

    </td>

    {{-- RETURN QTY --}}

    <td>

        <small class="text-primary">

            Remaining:
            {{ $remainingQty }}

        </small>

        <input type="number"
               step="0.01"
               min="0"

               max="{{ min(
                    $remainingQty,
                    $product->current_stock
               ) }}"

               name="quantity[]"

               class="
                    form-control
                    form-control-sm
                    qty
                    mt-1
               "

               data-row="{{ $key }}"

               value="0"

               {{
                    $remainingQty <= 0
                    || $product->current_stock <= 0
                    ? 'readonly'
                    : ''
               }}>

        @if($remainingQty <= 0)

            <small class="text-danger">

                Fully Returned

            </small>

        @elseif($product->current_stock <= 0)

            <small class="text-danger">

                Stock Empty

            </small>

        @endif

    </td>

    {{-- PRICE --}}

    <td>

        <input type="number"
               step="0.01"
               name="unit_price[]"

               class="
                    form-control
                    form-control-sm
                    price
               "

               data-row="{{ $key }}"

               value="{{ $item->unit_price }}"

               readonly>

    </td>

    {{-- VAT --}}

    <td>

        <input type="number"
               step="0.01"
               name="vat_rate[]"

               class="
                    form-control
                    form-control-sm
                    vat-rate
               "

               data-row="{{ $key }}"

               value="{{ $item->vat_rate }}"

               readonly>

    </td>

    {{-- VAT AMOUNT --}}

    <td>

        <input type="number"
               step="0.01"
               name="vat_amount[]"

               class="
                    form-control
                    form-control-sm
                    vat-amount
               "

               data-row="{{ $key }}"

               value="0"

               readonly>

    </td>

    {{-- TOTAL --}}

    <td>

        <input type="number"
               step="0.01"

               class="
                    form-control
                    form-control-sm
                    total
               "

               data-row="{{ $key }}"

               value="0"

               readonly>

    </td>

</tr>

@endforeach

                        </tbody>

                    </table>

                </div>

                {{-- TOTAL SECTION --}}

                <div class="row mt-4">

                    <div class="col-md-4 offset-md-8">

                        <table class="
                            table
                            table-bordered
                            table-sm
                        ">

                            <tr>

                                <th>

                                    Subtotal

                                </th>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           name="subtotal"
                                           id="subtotal"

                                           class="
                                                form-control
                                                form-control-sm
                                           "

                                           value="0"
                                           readonly>

                                </td>

                            </tr>

                            <tr>

                                <th>

                                    Total VAT

                                </th>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           name="total_vat"
                                           id="total_vat"

                                           class="
                                                form-control
                                                form-control-sm
                                           "

                                           value="0"
                                           readonly>

                                </td>

                            </tr>

                            <tr>

                                <th>

                                    Grand Total

                                </th>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           name="grand_total"
                                           id="grand_total"

                                           class="
                                                form-control
                                                form-control-sm
                                           "

                                           value="0"
                                           readonly>

                                </td>

                            </tr>

                        </table>

                        {{-- REFUND NOTE --}}

                        <div class="
                            alert
                            alert-info
                            mt-3
                        ">

                            <strong>

                                Note:

                            </strong>

                            Product return only.
                            Supplier refund payment
                            will be received separately.

                        </div>

                    </div>

                </div>

                {{-- SUBMIT --}}

                <div class="text-end mt-4">

                    <button type="submit"
                            class="
                                btn
                                btn-danger
                                px-4
                            ">

                        <i class="
                            fa-solid
                            fa-floppy-disk
                        "></i>

                        Save Purchase Return

                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

{{-- SCRIPT --}}

<script>

function calculateRow(row)
{
    let qty =
        parseFloat(
            document.querySelector(
                '.qty[data-row="'+row+'"]'
            ).value
        ) || 0;

    let price =
        parseFloat(
            document.querySelector(
                '.price[data-row="'+row+'"]'
            ).value
        ) || 0;

    let vatRate =
        parseFloat(
            document.querySelector(
                '.vat-rate[data-row="'+row+'"]'
            ).value
        ) || 0;

    let subtotal =
        qty * price;

    let vatAmount =
        (subtotal * vatRate)
        / 100;

    let total =
        subtotal + vatAmount;

    document.querySelector(
        '.vat-amount[data-row="'+row+'"]'
    ).value = vatAmount.toFixed(2);

    document.querySelector(
        '.total[data-row="'+row+'"]'
    ).value = total.toFixed(2);

    calculateSummary();
}

function calculateSummary()
{
    let subtotal = 0;

    let totalVat = 0;

    let grandTotal = 0;

    document.querySelectorAll('.total')
        .forEach(function (el) {

            grandTotal +=
                parseFloat(el.value) || 0;

        });

    document.querySelectorAll('.vat-amount')
        .forEach(function (el) {

            totalVat +=
                parseFloat(el.value) || 0;

        });

    subtotal =
        grandTotal - totalVat;

    document.getElementById(
        'subtotal'
    ).value = subtotal.toFixed(2);

    document.getElementById(
        'total_vat'
    ).value = totalVat.toFixed(2);

    document.getElementById(
        'grand_total'
    ).value = grandTotal.toFixed(2);
}

document.querySelectorAll('.qty')
    .forEach(function (input) {

        input.addEventListener(
            'input',
            function () {

                let max =
                    parseFloat(this.max) || 0;

                let value =
                    parseFloat(this.value) || 0;

                /**
                 * 🔥 BLOCK OVER RETURN
                 */

                if (value > max)
                {
                    this.value = max;

                    alert(
                        'Return qty exceeds remaining quantity.'
                    );
                }

                calculateRow(
                    this.dataset.row
                );

            }
        );

    });

</script>

<style>

.qty{

    min-width:90px;

}

.table td{

    vertical-align:middle;

    font-size:13px;

}

.table small{

    font-size:10px;

}

.form-control-sm{

    font-size:12px;

}

.alert{

    font-size:13px;

}

</style>

@endsection