@extends('company.layout')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">



<div class="container-fluid">
@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
<form action="{{ route('company.purchases.store') }}"
method="POST">

@csrf

@if ($errors->any())

<div class="alert alert-danger">

<ul class="mb-0">

@foreach($errors->all() as $error)

<li>{{ $error }}</li>

@endforeach

</ul>

</div>

@endif

<!-- TOP -->

<div class="card purchase-card mb-3">

    <div class="card-body">

        <div class="row g-2 align-items-end">

            {{-- INVOICE --}}

            <div class="col-md-2">

                <label class="erp-label">

                    Invoice No

                </label>

                <input type="text"
                       name="invoice_no"
                       value="{{ $invoiceNo }}"
                       class="form-control form-control-sm erp-input"
                       readonly>

            </div>

            {{-- DATE --}}

            <div class="col-md-2">

                <label class="erp-label">

                    Purchase Date

                </label>

                <input type="date"
name="purchase_date"
value="{{ old('purchase_date', date('Y-m-d')) }}"
class="form-control form-control-sm erp-input"
required>

            </div>

            {{-- BARCODE --}}

            <div class="col-md-2">

                <label class="erp-label">

                    Scan Barcode

                </label>

                <input type="text"
                       id="barcode_input"
                       class="form-control form-control-sm"
                       placeholder="Scan barcode"
                       autocomplete="off"
                       autofocus>

            </div>
<div class="col-md-2">

<label class="erp-label">
Financial Year
</label>

<select
name="financial_year_id"
class="form-select form-select-sm"
required>

@foreach($financialYears as $year)

<option value="{{ $year->id }}">
    {{ $year->name }}
</option>
@endforeach

</select>

</div>
            {{-- SUPPLIER --}}

            <div class="col-md-4">

                <label class="erp-label">

                    Supplier

                </label>

                <select name="supplier_id"
        id="supplier_select"
        class="form-select form-select-sm erp-input"
        required>

                    <option value="">

                        Select Supplier

                    </option>

                    @foreach($suppliers as $supplier)

                        <option
                            value="{{ $supplier->id }}"

                            data-balance="
                                {{ $supplier->current_balance }}
                            ">

                            {{ $supplier->name }}

                            |

                            Due:
                            {{ number_format(
                                $supplier->current_balance,
                                2
                            ) }}

                        </option>

                    @endforeach

                </select>

                <div
                    id="supplier_balance_box"
                    class="supplier-balance-box mt-2"
                    style="display:none;">

                </div>

            </div>

        </div>

    </div>

</div>

<!-- TABLE -->

<div class="card purchase-card mb-3">

    <div class="card-body table-responsive">

        <table class="table table-bordered purchase-table"
               id="purchaseTable">

            <thead>

                <tr>

                    <th style="width:30%;">

                        Product

                    </th>

                    <th>

                        Qty

                    </th>

                    <th>

                        Unit

                    </th>

                    <th>

                        Cost

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

                    <!-- PRODUCT -->

                    <td>

                        <select name="product_id[]"
                                class="form-select form-select-sm product-select"
                                required>

                            <option value="">

                                Select Product

                            </option>

                            @foreach($products as $product)

                                <option
                                    value="{{ $product->id }}"

                                    data-barcode="{{ $product->barcode }}"

                                    data-price="{{ $product->cost_price }}"

                                    data-unit="{{ $product->unit->name ?? '' }}"

                                    data-vat="{{ $product->vat_id }}"

                                    data-stock="{{ $product->current_stock }}">

                                    {{ $product->barcode }}

                                    |

                                    {{ $product->name }}

                                    |

                                    Stock:
                                    {{ $product->current_stock }}

                                </option>

                            @endforeach

                        </select>

                    </td>

                    <!-- QTY -->

                    <td>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="quantity[]"
                               class="form-control form-control-sm qty-input qty">

                        <div class="stock-box">

                            Current Stock : 0

                        </div>

                    </td>

                    <!-- UNIT -->

                    <td>

                        <input type="text"
                               class="form-control form-control-sm unit-input unit-name"
                               readonly>

                    </td>

                    <!-- COST -->

                    <td>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="unit_price[]"
                               class="form-control form-control-sm price-input price">

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

        <!-- ADD -->

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

            <!-- BUTTONS -->

            <div class="col-md-4 d-flex gap-2 align-items-start">

                <a href="{{ route(
                        'company.purchases.index'
                    ) }}"
                   class="btn btn-secondary px-4">

                    <i class="fa fa-arrow-left"></i>

                    Back

                </a>

                <button type="submit"
                        class="btn btn-primary px-4">

                    <i class="fa fa-save"></i>

                    Save Purchase

                </button>

            </div>

            <!-- TOTALS -->

            <div class="col-md-8">

                <div class="total-box">

                    <div class="row g-2">

                        <!-- SUBTOTAL -->

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

                        <!-- VAT TOTAL -->

                        <div class="col-md-4">

                            <label class="erp-label">

                                VAT Total

                            </label>

                            <input type="number"
                                   name="total_vat"
                                   id="totalVat"
                                   class="form-control form-control-sm"
                                   readonly>

                        </div>

                        <!-- GRAND TOTAL -->

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
<div class="col-md-4">

<label>
Payment Account
</label>

<select
name="account_id"
class="form-select form-select-sm">

<option value="">
Select Account
</option>

@foreach($accounts as $account)

<option value="{{ $account->id }}">

{{ $account->account_type }}

|

{{ $account->account_name }}

|

Balance:

{{ number_format(
    $account->current_balance,
    2
) }}

</option>

@endforeach

</select>

</div>
                        <!-- PAID -->

                        <div class="col-md-4">

                            <label class="erp-label">

                                Paid Amount

                            </label>

                            <input type="number"
                                   min="0"
                                   step="0.01"
                                   name="paid_amount"
                                   id="paidAmount"
                                   value="{{ old('paid_amount',0) }}"
                                   class="form-control form-control-sm">

                        </div>

                        <!-- DUE -->

                        <div class="col-md-4">

                            <label class="erp-label">

                                Due Amount

                            </label>

                           <input type="number"
id="dueAmount"
class="form-control form-control-sm"
readonly>

                        </div>

                        <!-- NOTE -->

                        <div class="col-md-4">

                            <label class="erp-label">

                                Note

                            </label>

                           <input type="text"
name="note"
value="{{ old('note') }}"
class="form-control form-control-sm">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</form>

</div>
<script>

/**
|--------------------------------------------------------------------------
| 🔥 SINGLE ROW CALCULATION
|--------------------------------------------------------------------------
*/

function calculateRow(row)
{
    let qty =
        parseFloat(
            row.querySelector('.qty').value
        ) || 0;

    let price =
        parseFloat(
            row.querySelector('.price').value
        ) || 0;

    let vatSelect =
        row.querySelector('.vat-select');

    let vatRate =
        parseFloat(
            vatSelect.options[
                vatSelect.selectedIndex
            ]?.dataset.rate
        ) || 0;

    row.querySelector('.vat-rate').value =
        vatRate;

    let subtotal =
        qty * price;

    let vatAmount =
        (subtotal * vatRate) / 100;

    let total =
        subtotal + vatAmount;

    row.querySelector('.vat-amount').value =
        vatAmount.toFixed(2);

    row.querySelector('.total-price').value =
        total.toFixed(2);

    calculateTotals();
}

/**
|--------------------------------------------------------------------------
| 🔥 TOTAL CALCULATION
|--------------------------------------------------------------------------
*/

function calculateTotals()
{
    let totalVat = 0;

    let grandTotal = 0;

    document.querySelectorAll('.vat-amount')
        .forEach(function(input){

            totalVat +=
                parseFloat(input.value) || 0;

        });

    document.querySelectorAll('.total-price')
        .forEach(function(input){

            grandTotal +=
                parseFloat(input.value) || 0;

        });

    let subtotal =
        grandTotal - totalVat;

    document.getElementById(
        'subtotal'
    ).value =
        subtotal.toFixed(2);

    document.getElementById(
        'totalVat'
    ).value =
        totalVat.toFixed(2);

    document.getElementById(
        'grandTotal'
    ).value =
        grandTotal.toFixed(2);

    let paid =
        parseFloat(
            document.getElementById(
                'paidAmount'
            ).value
        ) || 0;

    // BLOCK OVER PAYMENT

    if (paid > grandTotal)
    {
        paid = grandTotal;

        document.getElementById(
            'paidAmount'
        ).value =
            grandTotal.toFixed(2);

        alert(
            'Paid amount exceeds total.'
        );
    }

    document.getElementById(
        'dueAmount'
    ).value =
        (grandTotal - paid).toFixed(2);
}

/**
|--------------------------------------------------------------------------
| 🔥 PRODUCT CHANGE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'change',
    function(e){

        /**
         * PRODUCT SELECT
         */

        if (
            e.target.classList.contains(
                'product-select'
            )
        )
        {
            let row =
                e.target.closest('tr');

            let selected =
                e.target.options[
                    e.target.selectedIndex
                ];

            /**
             * PRICE
             */

            row.querySelector('.price').value =
                selected.dataset.price || 0;

            /**
             * UNIT
             */

            row.querySelector('.unit-name').value =
                selected.dataset.unit || '';

            /**
             * VAT
             */

            let vatId =
                selected.dataset.vat || '';

            let vatSelect =
                row.querySelector('.vat-select');

            if (vatId)
            {
                vatSelect.value = vatId;
            }

            /**
             * STOCK
             */

            let stock =
                parseFloat(
                    selected.dataset.stock
                ) || 0;

            let stockBox =
                row.querySelector(
                    '.stock-box'
                );

            if (stockBox)
            {
                stockBox.innerHTML =
                    'Current Stock : ' +
                    stock;

                stockBox.style.color =
                    stock > 0
                    ? '#22c55e'
                    : '#ef4444';
            }

            calculateRow(row);
        }

        /**
         * QTY / PRICE / VAT CHANGE
         */

        if (
            e.target.classList.contains('qty') ||
            e.target.classList.contains('price') ||
            e.target.classList.contains('vat-select')
        )
        {
            calculateRow(
                e.target.closest('tr')
            );
        }

    }
);

/**
|--------------------------------------------------------------------------
| 🔥 BARCODE SCAN
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'barcode_input'
    )
    .addEventListener(
        'keydown',
        function(e){

            if (e.key !== 'Enter')
            {
                return;
            }

            e.preventDefault();

            let barcode =
                this.value.trim();

            if (!barcode)
            {
                return;
            }

            let row =
                document.querySelector(
                    '#purchaseTable tbody tr:last-child'
                );

            let select =
                row.querySelector(
                    '.product-select'
                );

            let found = false;

            Array.from(
                select.options
            ).forEach(function(option){

                if (
                    option.dataset.barcode ==
                    barcode
                )
                {
                    found = true;

                    /**
                     * AUTO SELECT
                     */

                    select.value =
                        option.value;

                    /**
                     * TRIGGER CHANGE
                     */

                    select.dispatchEvent(
                        new Event('change')
                    );

                    /**
                     * AUTO QTY
                     */

                    row.querySelector(
                        '.qty'
                    ).value = 1;

                    calculateRow(row);
                }

            });

            /**
             * NOT FOUND
             */

            if (!found)
            {
                alert(
                    'Product not found.'
                );
            }

            /**
             * RESET
             */

            this.value = '';

        }
);

/**
|--------------------------------------------------------------------------
| 🔥 PAID AMOUNT
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'paidAmount'
    )
    .addEventListener(
        'input',
        calculateTotals
    );

/**
|--------------------------------------------------------------------------
| 🔥 SUPPLIER BALANCE
|--------------------------------------------------------------------------
*/

let supplierSelect =
    document.getElementById(
        'supplier_select'
    );

let supplierBalanceBox =
    document.getElementById(
        'supplier_balance_box'
    );

if (supplierSelect)
{
    supplierSelect.addEventListener(
        'change',
        function(){

            let option =
                this.options[
                    this.selectedIndex
                ];

            let balance =
                parseFloat(
                    option.dataset.balance
                ) || 0;

            if (this.value)
            {
                supplierBalanceBox.style.display =
                    'block';

                supplierBalanceBox.innerHTML =
                    'Current Due : ' +
                    balance.toLocaleString();
            }
            else
            {
                supplierBalanceBox.style.display =
                    'none';
            }

        }
    );
}

/**
|--------------------------------------------------------------------------
| 🔥 ADD ROW
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'addRow'
    )
    .addEventListener(
        'click',
        function(){

            let tbody =
                document.querySelector(
                    '#purchaseTable tbody'
                );

            let firstRow =
                tbody.querySelector('tr');

            let newRow =
                firstRow.cloneNode(true);

            /**
             * RESET INPUTS
             */

            newRow.querySelectorAll('input')
                .forEach(function(input){

                    if (
                        input.type !== 'hidden'
                    )
                    {
                        input.value = '';
                    }

                });

            /**
             * RESET SELECTS
             */

            newRow.querySelectorAll('select')
                .forEach(function(select){

                    select.selectedIndex = 0;

                });

            /**
             * RESET STOCK
             */

            let stockBox =
                newRow.querySelector(
                    '.stock-box'
                );

            if (stockBox)
            {
                stockBox.innerHTML =
                    'Current Stock : 0';
            }

            tbody.appendChild(newRow);

        }
);

/**
|--------------------------------------------------------------------------
| 🔥 REMOVE ROW
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'click',
    function(e){

        if (
            e.target.classList.contains(
                'removeRow'
            )
        )
        {
            let rows =
                document.querySelectorAll(
                    '#purchaseTable tbody tr'
                );

            if (rows.length > 1)
            {
                e.target
                    .closest('tr')
                    .remove();

                calculateTotals();
            }
        }

    }
);

</script>

@endsection