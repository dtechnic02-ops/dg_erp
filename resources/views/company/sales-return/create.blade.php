@extends('company.layout')

@section('content')

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
<div class="container-fluid py-3">

    <form action="{{ route('company.sales-return.store') }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf

        {{-- 🔥 HIDDEN --}}
        
        <input type="hidden"
               name="sales_invoice_id"
               value="{{ $invoice->id }}">

        <input type="hidden"
               name="customer_id"
               value="{{ $invoice->customer_id }}">

        {{-- PAGE HEADER --}}

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h4 class="text-white mb-0">

                ↩️ Sales Return

            </h4>

            <button type="submit"
                    class="btn btn-danger">

                Save Return

            </button>

        </div>

        {{-- HEADER CARD --}}

        <div class="card shadow-sm border-0 rounded-3 mb-4">

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label fw-bold">

                            Return No

                        </label>

                        <input type="text"
                               name="return_no"
                               value="{{ $returnNo }}"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-bold">

                            Invoice No

                        </label>

                        <input type="text"
                               value="{{ $invoice->invoice_no }}"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-bold">

                            Customer

                        </label>

                        <input type="text"
                               value="{{ $invoice->customer->name ?? '' }}"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-bold">

                            Return Date

                        </label>

                        <input type="date"
                               name="return_date"
                               value="{{ now()->format('Y-m-d') }}"
                               class="form-control"
                               readonly>

                    </div>

                </div>

            </div>

        </div>

        {{-- DAMAGE PHOTO --}}

        <div class="card shadow-sm border-0 rounded-3 mb-4">

            <div class="card-header bg-white border-0">

                <h5 class="mb-0">

                    📸 Damage Photo

                </h5>

            </div>

            <div class="card-body">

                <input type="file"
                       name="damage_photo"
                       class="form-control">

                <small class="text-muted">

                    Upload damaged item proof image if available.

                </small>

            </div>

        </div>

        {{-- RETURN ITEMS --}}

        <div class="card shadow-sm border-0 rounded-3 mb-4">

            <div class="card-header bg-white border-0">

                <h5 class="mb-0">

                    📦 Return Items

                </h5>

            </div>

            <div class="card-body table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Item</th>

                            <th width="120">

                                Sold Qty

                            </th>

                            <th width="140">

                                Return Qty

                            </th>

                            <th width="140">

                                Unit Price

                            </th>

                            <th width="100">

                                VAT %

                            </th>

                            <th width="160">

                                Total

                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($invoice->items as $item)

                        {{-- 🔥 PRODUCT ONLY --}}

                        @if($item->item_type == 'product')

                            <tr>

                                {{-- PRODUCT --}}

                                <td>

                                    <strong>

                                        {{ $item->product->name ?? '' }}

                                    </strong>

                                    <input type="hidden"
                                           name="product_id[]"
                                           value="{{ $item->product_id }}">

                                    <input type="hidden"
                                           name="sales_item_id[]"
                                           value="{{ $item->id }}">

                                </td>

                                {{-- SOLD QTY --}}

                                <td>

                                    <span class="badge bg-primary fs-6">

                                        {{

                                            $item->quantity

                                            -

                                            $item->returned_qty

                                        }}

                                    </span>

                                </td>

                                {{-- RETURN QTY --}}

                                <td>

                                    <input type="number"
                                           name="quantity[]"
                                           class="form-control return-qty"
                                           min="0"

                                           max="{{
                                                $item->quantity
                                                -
                                                $item->returned_qty
                                           }}"

                                           value="0">

                                </td>

                                {{-- UNIT PRICE --}}

                                <td>

                                    {{ number_format(
                                        $item->unit_price,
                                        2
                                    ) }}

                                    <input type="hidden"
                                           name="unit_price[]"
                                           class="unit-price"
                                           value="{{ $item->unit_price }}">

                                </td>

                                {{-- VAT --}}

                                <td>

                                    {{ $item->vat_rate }}%

                                    <input type="hidden"
                                           name="vat_rate[]"
                                           class="vat-rate"
                                           value="{{ $item->vat_rate }}">

                                </td>

                                {{-- TOTAL --}}

                                <td>

                                    <input type="text"
                                           class="form-control row-total bg-light"
                                           value="0.00"
                                           readonly>

                                </td>

                            </tr>

                        @endif

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        {{-- FOOTER --}}

        <div class="card shadow-sm border-0 rounded-3">

            <div class="card-body">

                <div class="row">

                    {{-- NOTE --}}

                    <div class="col-md-7">

                        <label class="form-label fw-bold">

                            Note

                        </label>

                        <textarea
                            name="note"
                            class="form-control"
                            rows="5"
                            placeholder="
                                Return reason or notes...
                            "></textarea>

                    </div>

                    {{-- TOTAL --}}

                    <div class="col-md-5">

                        <div class="border rounded-3 p-4 bg-light">

                          <h5 class="mb-3">

    💰 Return Summary

</h5>

<div class="mb-3">

    <label class="form-label fw-bold">

        Subtotal

    </label>

    <input type="text"
           id="subtotal"
           class="form-control"
           value="0.00"
           readonly>

</div>

<div class="mb-3">

    <label class="form-label fw-bold">

        VAT

    </label>

    <input type="text"
           id="totalVat"
           class="form-control"
           value="0.00"
           readonly>

</div>

<div class="mb-3">

    <label class="form-label fw-bold">

        Grand Total

    </label>

    <input type="text"
           name="grand_total"
           id="grandTotal"
           class="
                form-control
                form-control-lg
                fw-bold
           "
           value="0.00"
           readonly>

</div>

                            <button type="submit"
                                    class="btn btn-danger w-100">

                                Save Return

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function ()
{

    /**
     * 🔥 TOTAL CALCULATION
     */

    function calculateTotals()
{
    let subtotalTotal = 0;

    let vatTotal = 0;

    let grandTotal = 0;

    document.querySelectorAll(
        'tbody tr'
    )
    .forEach(function (row)
    {

        let qty = parseFloat(

            row.querySelector(
                '.return-qty'
            ).value

        ) || 0;

        let maxQty = parseFloat(

            row.querySelector(
                '.return-qty'
            ).max

        ) || 0;

        if (qty < 0)
        {
            qty = 0;

            row.querySelector(
                '.return-qty'
            ).value = 0;
        }

        if (qty > maxQty)
        {
            qty = maxQty;

            row.querySelector(
                '.return-qty'
            ).value = maxQty;
        }

        let price = parseFloat(

            row.querySelector(
                '.unit-price'
            ).value

        ) || 0;

        let vatRate = parseFloat(

            row.querySelector(
                '.vat-rate'
            ).value

        ) || 0;

        /**
         * SUBTOTAL
         */

        let subtotal =
            qty * price;

        /**
         * VAT
         */

        let vatAmount =

            (subtotal * vatRate)

            / 100;

        /**
         * TOTAL
         */

        let total =

            subtotal + vatAmount;

        row.querySelector(
            '.row-total'
        ).value = total.toFixed(2);

        subtotalTotal += subtotal;

        vatTotal += vatAmount;

        grandTotal += total;
    });

    /**
     * SUMMARY
     */

    document.getElementById(
        'subtotal'
    ).value =
        subtotalTotal.toFixed(2);

    document.getElementById(
        'totalVat'
    ).value =
        vatTotal.toFixed(2);

    document.getElementById(
        'grandTotal'
    ).value =
        grandTotal.toFixed(2);
}

    /**
     * 🔥 LIVE EVENT
     */

    document.addEventListener(
        'input',
        function (e)
    {

        if (
            e.target.classList.contains(
                'return-qty'
            )
        ) {

            calculateTotals();
        }

    });

});

</script>

@endsection