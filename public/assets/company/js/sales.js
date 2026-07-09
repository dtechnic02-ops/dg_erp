/*=========================================================
DG ERP

Sales Invoice

sales.js

Version : Final

=========================================================*/


/*=========================================================
DOM CACHE
=========================================================*/

const salesForm =
document.getElementById('salesForm');

const salesBody =
document.getElementById('salesBody');

const addRowButton =
document.getElementById('addRow');

const customerSelect =
document.getElementById('customer_id');

const accountSelect =
document.getElementById('account_id');

const priceType =
document.getElementById('price_type');

const paidAmount =
document.getElementById('paid_amount');

const discountAmount =
document.getElementById('discount_amount');

const subtotalInput =
document.getElementById('subtotal');

const vatInput =
document.getElementById('total_vat');

const grandTotalInput =
document.getElementById('grand_total');

const subtotalDisplay =
document.getElementById('subtotal_display');

const vatDisplay =
document.getElementById('vat_display');

const grandDisplay =
document.getElementById('grand_total_display');

const paidDisplay =
document.getElementById('paid_display');

const dueDisplay =
document.getElementById('due_display');


/*=========================================================
HELPER
=========================================================*/

function money(value)
{
    const number =
        parseFloat(value);

    return isNaN(number)
        ? 0
        : number;
}


function format(value)
{
    return money(value).toFixed(2);
}


/*=========================================================
INITIALIZE
=========================================================*/

document.addEventListener(

    'DOMContentLoaded',

    initializeSales

);


function initializeSales()
{
    if (!salesBody)
    {
        return;
    }

    bindEvents();

    updateRowNumbers();

    customerChanged();

    accountChanged();

    calculateInvoice();

}
/*=========================================================
EVENTS
=========================================================*/

function bindEvents()
{

    if(addRowButton)
    {
        addRowButton.addEventListener(

            'click',

            addRow

        );
    }


    if(customerSelect)
    {
        customerSelect.addEventListener(

            'change',

            customerChanged

        );
    }


    if(accountSelect)
    {
        accountSelect.addEventListener(

            'change',

            accountChanged

        );
    }


    if(priceType)
    {
        priceType.addEventListener(

            'change',

            priceTypeChanged

        );
    }


    if(paidAmount)
    {
        paidAmount.addEventListener(

            'input',

            calculateInvoice

        );
    }


    if(discountAmount)
    {
        discountAmount.addEventListener(

            'input',

            calculateInvoice

        );
    }


    salesBody.addEventListener(

        'change',

        tableChanged

    );


    salesBody.addEventListener(

        'input',

        tableChanged

    );


    salesBody.addEventListener(

        'click',

        tableClicked

    );

}
/*=========================================================
CUSTOMER
=========================================================*/

function customerChanged()
{
    if(!customerSelect)
    {
        return;
    }

    const option =
        customerSelect.options[
            customerSelect.selectedIndex
        ];

    const mobile =
        document.getElementById(
            'customer_mobile'
        );

    const balance =
        document.getElementById(
            'customer_balance'
        );

    if(mobile)
    {
        mobile.value =
            option.dataset.mobile || '';
    }

    if(balance)
    {
        balance.innerHTML =
            'Balance : ' +
            (option.dataset.balance || '0.00');
    }

}


/*=========================================================
ACCOUNT
=========================================================*/

function accountChanged()
{
    if(!accountSelect)
    {
        return;
    }

    const option =
        accountSelect.options[
            accountSelect.selectedIndex
        ];

    const balance =
        document.getElementById(
            'account_balance'
        );

    if(balance)
    {
        balance.innerHTML =
            'Balance : ' +
            (option.dataset.balance || '0.00');
    }

}


/*=========================================================
PRICE TYPE
=========================================================*/

function priceTypeChanged()
{
    salesBody
        .querySelectorAll('.sales-row')
        .forEach(function(row){

            if(
                row.querySelector('.item-type').value === 'product'
            )
            {
                productChanged(row);
            }

        });
}

/*=========================================================
PRODUCT
=========================================================*/

function productChanged(row)
{
    const product =
        row.querySelector(
            '.product-select'
        );

  if(!product.value)
{
    row.querySelector('.unit-name').value = '';

    row.querySelector('.unit-id').value = '';

    row.querySelector('.product-stock').innerHTML =
        'Stock : 0';

    row.querySelector('.unit-price').value = 0;

    row.querySelector('.vat-select').selectedIndex = 0;

    row.querySelector('.vat-rate').value = 0;

    calculateRow(row);

    calculateInvoice();

    return;
}

    const option =
        product.options[
            product.selectedIndex
        ];

    /*-------------------------
    PRICE
    -------------------------*/

    let price = 0;

    if(
        priceType.value ===
        'wholesale'
    )
    {
        price =
            option.dataset.wholesale;
    }
    else
    {
        price =
            option.dataset.retail;
    }

    row.querySelector(
        '.unit-price'
    ).value = money(price);

    /*-------------------------
    UNIT
    -------------------------*/

    row.querySelector(
        '.unit-name'
    ).value =
        option.dataset.unit || '';

    row.querySelector(
        '.unit-id'
    ).value =
        option.dataset.unitId || '';

    /*-------------------------
    STOCK
    -------------------------*/

    const stock =
        row.querySelector(
            '.product-stock'
        );

    if(stock)
    {
        stock.innerHTML =
            'Stock : ' +
            (option.dataset.stock || 0);
    }

    /*-------------------------
    VAT
    -------------------------*/

    const vatSelect =
        row.querySelector(
            '.vat-select'
        );

    if (vatSelect)
{
    vatSelect.value =
        option.dataset.vatId || 1;

    vatChanged(row);
}

}


/*=========================================================
SERVICE
=========================================================*/

function serviceChanged(row)
{
    const service =
        row.querySelector(
            '.service-select'
        );

    if(!service.value)
    {
        return;
    }

    const option =
        service.options[
            service.selectedIndex
        ];

    row.querySelector(
        '.unit-price'
    ).value =
        money(option.dataset.price);

    row.querySelector(
        '.unit-name'
    ).value = 'Service';

    row.querySelector(
        '.unit-id'
    ).value = '';

const vatSelect =
    row.querySelector('.vat-select');

if (vatSelect)
{
    vatSelect.value =
        option.dataset.vatId || 1;

    vatChanged(row);
}

    calculateInvoice();

}


/*=========================================================
VAT
=========================================================*/

function vatChanged(row)
{
    const vat =
        row.querySelector(
            '.vat-select'
        );

    if(!vat)
    {
        return;
    }

    const option =
        vat.options[
            vat.selectedIndex
        ];

    row.querySelector(
        '.vat-rate'
    ).value =
        option.dataset.rate || 0;

    calculateRow(row);

    calculateInvoice();

}
/*=========================================================
TABLE EVENTS
=========================================================*/

function tableClicked(event)
{
    const removeButton =
        event.target.closest('.remove-row');

    if (!removeButton)
    {
        return;
    }

    removeRow(removeButton);
}


function tableChanged(event)
{
    const row =
        event.target.closest('.sales-row');

    if (!row)
    {
        return;
    }

    /*---------------------------------
    ITEM TYPE
    ---------------------------------*/

    if (
        event.target.classList.contains(
            'item-type'
        )
    )
    {
        itemTypeChanged(row);

        return;
    }

    /*---------------------------------
    PRODUCT
    ---------------------------------*/

    if (
        event.target.classList.contains(
            'product-select'
        )
    )
    {
        productChanged(row);

        return;
    }

    /*---------------------------------
    SERVICE
    ---------------------------------*/

    if (
        event.target.classList.contains(
            'service-select'
        )
    )
    {
        serviceChanged(row);

        return;
    }

    /*---------------------------------
    VAT
    ---------------------------------*/

    if (
        event.target.classList.contains(
            'vat-select'
        )
    )
    {
        vatChanged(row);

        return;
    }

    /*---------------------------------
    QTY / PRICE
    ---------------------------------*/

    calculateRow(row);

    calculateInvoice();
}


/*=========================================================
ITEM TYPE
=========================================================*/

function itemTypeChanged(row)
{
    const type =
        row.querySelector('.item-type').value;

    const product =
        row.querySelector('.product-select');

    const service =
        row.querySelector('.service-select');

    /*---------------------------------
    RESET
    ---------------------------------*/

    product.value = '';

    service.value = '';

    row.querySelector('.unit-name').value = '';

    row.querySelector('.unit-id').value = '';

    row.querySelector('.unit-price').value = 0;

    row.querySelector('.vat-select').selectedIndex = 0;

    row.querySelector('.vat-rate').value = 0;

    row.querySelector('.vat-amount').value = 0;

    row.querySelector('.total-price').value = 0;

    const stock =
        row.querySelector('.product-stock');

    if (stock)
    {
        stock.innerHTML = 'Stock : 0';
    }

    /*---------------------------------
    SHOW / HIDE
    ---------------------------------*/

    if (type === 'product')
    {
        product.classList.remove('d-none');

        service.classList.add('d-none');
    }
    else
    {
        service.classList.remove('d-none');

        product.classList.add('d-none');
    }
    calculateRow(row);
    calculateInvoice();
}
/*=========================================================
ROW CALCULATION
=========================================================*/

function calculateRow(row)
{
    const quantity =
        money(
            row.querySelector(
                '.quantity'
            ).value
        );

    const unitPrice =
        money(
            row.querySelector(
                '.unit-price'
            ).value
        );

    const vatRate =
        money(
            row.querySelector(
                '.vat-rate'
            ).value
        );

    /*---------------------------------
    SUB TOTAL
    ---------------------------------*/

    const subTotal =
        quantity * unitPrice;

    /*---------------------------------
    VAT AMOUNT
    ---------------------------------*/

    const vatAmount =
        (subTotal * vatRate) / 100;

    /*---------------------------------
    ROW TOTAL
    ---------------------------------*/

    const rowTotal =
        subTotal + vatAmount;

    /*---------------------------------
    UPDATE ROW
    ---------------------------------*/

    row.querySelector(
        '.vat-amount'
    ).value =
        format(vatAmount);

    row.querySelector(
        '.total-price'
    ).value =
        format(rowTotal);

}


/*=========================================================
INVOICE CALCULATION
=========================================================*/

function calculateInvoice()
{
    let subtotal = 0;

    let totalVat = 0;

    salesBody
        .querySelectorAll(
            '.sales-row'
        )
        .forEach(function(row){

            subtotal +=
                money(
                    row.querySelector(
                        '.quantity'
                    ).value
                ) *
                money(
                    row.querySelector(
                        '.unit-price'
                    ).value
                );

            totalVat +=
                money(
                    row.querySelector(
                        '.vat-amount'
                    ).value
                );

        });

    const discount =
        money(
            discountAmount.value
        );

   const grandTotal =
Math.max(
    0,
    subtotal -
    discount +
    totalVat
);

    const paid =
        money(
            paidAmount.value
        );

    const due =
        Math.max(
            0,
            grandTotal - paid
        );

    /*---------------------------------
    HIDDEN INPUT
    ---------------------------------*/

    subtotalInput.value =
        format(subtotal);

    vatInput.value =
        format(totalVat);

    grandTotalInput.value =
        format(grandTotal);

    /*---------------------------------
    DISPLAY
    ---------------------------------*/

    subtotalDisplay.value =
        format(subtotal);

    vatDisplay.value =
        format(totalVat);

    grandDisplay.value =
        format(grandTotal);

    paidDisplay.value =
        format(paid);

    dueDisplay.value =
        format(due);
}
/*=========================================================
ADD ROW
=========================================================*/

function addRow()
{
    const firstRow =
        salesBody.querySelector('.sales-row');

    const newRow =
        firstRow.cloneNode(true);

    /*---------------------------------
    RESET INPUT
    ---------------------------------*/

    newRow.querySelectorAll('input')
        .forEach(function(input){

       if (input.classList.contains('quantity'))
{
    input.value = 1;
}
else if (input.classList.contains('unit-name'))
{
    input.value = '';
}
else
{
    input.value = 0;
}

        });

    /*---------------------------------
    RESET SELECT
    ---------------------------------*/

    newRow.querySelectorAll('select')
        .forEach(function(select){

            select.selectedIndex = 0;

        });

    /*---------------------------------
    DEFAULT VALUE
    ---------------------------------*/

    newRow.querySelector(
        '.product-select'
    ).classList.remove('d-none');

    newRow.querySelector(
        '.service-select'
    ).classList.add('d-none');

    newRow.querySelector(
        '.product-stock'
    ).innerHTML =
        'Stock : 0';

    newRow.querySelector(
        '.unit-price'
    ).value = 0;

    newRow.querySelector(
        '.vat-rate'
    ).value = 0;

    newRow.querySelector(
        '.vat-amount'
    ).value = 0;

    newRow.querySelector(
        '.total-price'
    ).value = 0;

    salesBody.appendChild(newRow);

    updateRowNumbers();

    calculateInvoice();
}


/*=========================================================
REMOVE ROW
=========================================================*/

function removeRow(button)
{
    if (
        salesBody.querySelectorAll(
            '.sales-row'
        ).length <= 1
    )
    {
        alert(
            'Minimum one item required.'
        );

        return;
    }

    button.closest('.sales-row')
        .remove();

    updateRowNumbers();

    calculateInvoice();
}


/*=========================================================
ROW NUMBER
=========================================================*/

function updateRowNumbers()
{
    salesBody.querySelectorAll(
        '.sales-row'
    ).forEach(function(
        row,
        index
    ){

        row.querySelector(
            '.row-number'
        ).textContent =
            index + 1;

    });
}


/*=========================================================
VALIDATION
=========================================================*/

function validateInvoice()
{
    let valid = true;

    salesBody.querySelectorAll(
        '.sales-row'
    ).forEach(function(row){

        const type =
            row.querySelector(
                '.item-type'
            ).value;

        if (
            type === 'product'
        )
        {
            if (
                !row.querySelector(
                    '.product-select'
                ).value
            )
            {
                valid = false;
            }
        }

        if (
            type === 'service'
        )
        {
            if (
                !row.querySelector(
                    '.service-select'
                ).value
            )
            {
                valid = false;
            }
        }

        if (
            money(
                row.querySelector(
                    '.quantity'
                ).value
            ) <= 0
        )
        {
            valid = false;
        }

    });

    if (!valid)
    {
        alert(
            'Please complete all required fields.'
        );
    }

    return valid;
}


/*=========================================================
FORM SUBMIT
=========================================================*/

if (salesForm)
{
    salesForm.addEventListener(

        'submit',

        function(event){

            calculateInvoice();

            if (
                !validateInvoice()
            )
            {
                event.preventDefault();

                return;
            }

            const saveButton =
                document.getElementById(
                    'saveInvoice'
                );

            if(saveButton)
            {
                saveButton.disabled = true;

                saveButton.innerHTML =
                    'Saving...';
            }

        }

    );
}