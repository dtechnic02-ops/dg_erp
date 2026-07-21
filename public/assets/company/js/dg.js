/* =========================================================
   DG ERP - UI FRAMEWORK JAVASCRIPT
   File     : dg.js
   Standard : DG ERP Master UI Framework Standard v1.0
   ---------------------------------------------------------
   ONE JavaScript Framework for the entire ERP.
   Every reusable function lives inside this single file.
   Module specific behaviour is namespaced under DG.<module>
   so multiple modules can safely share this one file.
   ========================================================= */

window.DG = window.DG || {};

/* =========================================================
   MODULE : SALES BILLING (Create Sales Invoice)
   Screen : resources/views/company/sales/create.blade.php
   ========================================================= */

DG.salesBilling = (function () {

    'use strict';

    var form = null;
    var itemsBody = null;
    var rowTemplate = null;

    /* ---------------------------------------------------
       HELPERS
    --------------------------------------------------- */

    function toNumber(value) {
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function toMoney(value) {
        return toNumber(value).toFixed(2);
    }

    function qs(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function qsa(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function setValue(selector, value) {
        var field = qs(selector);

        if (field) {
            field.value = value;
        }
    }

    /* ---------------------------------------------------
       AUTOMATIC ROW NUMBER
    --------------------------------------------------- */

    function renumberRows() {
        var rows = qsa('tr.dg-row', itemsBody);

        rows.forEach(function (row, index) {
            var numberCell = row.children[0];

            if (numberCell) {
                numberCell.textContent = index + 1;
            }

            var deleteBtn = qs('button.btn-outline-danger', row);

            if (deleteBtn) {
                deleteBtn.setAttribute('aria-label', 'Delete row ' + (index + 1));
            }
        });
    }

    /* ---------------------------------------------------
       ADD ITEM
    --------------------------------------------------- */

    function nextRowIndex() {
        return qsa('tr.dg-row', itemsBody).length;
    }

    function reindexClone(clone, newIndex) {
        var deleteBtn = qs('button.btn-outline-danger', clone);

        if (deleteBtn) {
            deleteBtn.setAttribute('aria-label', 'Delete row ' + (newIndex + 1));
        }
    }

    function resetClone(clone) {
        qsa('select', clone).forEach(function (select) {
            select.selectedIndex = 0;
            select.classList.remove('is-invalid');
        });

        qsa('input[type="hidden"]', clone).forEach(function (input) {
            input.value = '';
        });

        qsa('input[type="number"]', clone).forEach(function (input) {
            input.value = input.readOnly ? '0' : '';
            input.classList.remove('is-invalid');
            input.removeAttribute('max');
        });

        qsa('input[type="text"]', clone).forEach(function (input) {
            if (input.readOnly) {
                input.value = '-';
            }
        });
    }

    function addRow() {
        var newIndex = nextRowIndex();
        var clone = rowTemplate.cloneNode(true);

        reindexClone(clone, newIndex);
        resetClone(clone);
        updateItemSelectsByType(clone);

        itemsBody.appendChild(clone);
        renumberRows();

        return clone;
    }

    /* ---------------------------------------------------
       DELETE ITEM
    --------------------------------------------------- */

    function deleteRow(row) {
        var rows = qsa('tr.dg-row', itemsBody);

        if (rows.length <= 1) {
            resetClone(row);
            updateItemSelectsByType(row);
            recalcRow(row);
            return;
        }

        row.parentNode.removeChild(row);
        renumberRows();
        recalcSummary();
    }

    /* ---------------------------------------------------
       PRODUCT / SERVICE SELECTION
    --------------------------------------------------- */

    function getProductSelect(row) {
        return qs('select.dg-product-select', row);
    }

    function getServiceSelect(row) {
        return qs('select.dg-service-select', row);
    }

    function getActiveItemSelect(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);
        var type = typeSelect ? typeSelect.value : '';

        if (type === 'product') {
            return getProductSelect(row);
        }

        if (type === 'service') {
            return getServiceSelect(row);
        }

        return null;
    }

    function syncItemIdFields(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);
        var productField = qs('input.dg-product-id', row);
        var serviceField = qs('input.dg-service-id', row);
        var productSelect = getProductSelect(row);
        var serviceSelect = getServiceSelect(row);

        if (!productField || !serviceField) {
            return;
        }

        var type = typeSelect ? typeSelect.value : '';

        productField.value = '';
        serviceField.value = '';

        if (type === 'product' && productSelect && productSelect.value) {
            productField.value = productSelect.value;
            return;
        }

        if (type === 'service' && serviceSelect && serviceSelect.value) {
            serviceField.value = serviceSelect.value;
        }
    }

    function syncAllItemIdFields() {
        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            syncItemIdFields(row);
        });
    }

    function updateItemSelectsByType(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);
        var productPicker = qs('.dg-product-picker', row);
        var servicePicker = qs('.dg-service-picker', row);
        var productSelect = getProductSelect(row);
        var serviceSelect = getServiceSelect(row);
        var type = typeSelect ? typeSelect.value : '';

        if (productPicker) {
            productPicker.hidden = (type !== 'product');
        }

        if (servicePicker) {
            servicePicker.hidden = (type !== 'service');
        }

        if (type === 'service' && productSelect) {
            productSelect.selectedIndex = 0;
            productSelect.classList.remove('is-invalid');
        }

        if (type === 'product' && serviceSelect) {
            serviceSelect.selectedIndex = 0;
            serviceSelect.classList.remove('is-invalid');
        }

        if (!type) {
            if (productSelect) {
                productSelect.selectedIndex = 0;
                productSelect.classList.remove('is-invalid');
            }

            if (serviceSelect) {
                serviceSelect.selectedIndex = 0;
                serviceSelect.classList.remove('is-invalid');
            }
        }

        applySelectedItemData(row);
        syncItemIdFields(row);
    }

    /* ---------------------------------------------------
       UNIT AUTO DISPLAY / UNIT PRICE AUTO-FILL / STOCK DISPLAY
       ---------------------------------------------------
       Reads data-unit, data-retail-price, data-wholesale-price,
       data-price and data-stock attributes from the selected
       <option> element (rendered from the Product / Service
       models in create.blade.php). Products carry separate
       Retail / Wholesale prices, selected using the current
       #price_type value; Services carry a single price.
       If the option does not carry this data, the fields
       safely fall back to their default state instead of
       fabricating values.
    --------------------------------------------------- */

    function resolveSellingPrice(option) {
        var priceTypeField = qs('#price_type');
        var priceType = priceTypeField ? priceTypeField.value : 'retail';

        if (priceType === 'wholesale' && option.hasAttribute('data-wholesale-price')) {
            return option.getAttribute('data-wholesale-price');
        }

        if (option.hasAttribute('data-retail-price')) {
            return option.getAttribute('data-retail-price');
        }

        if (option.hasAttribute('data-price')) {
            return option.getAttribute('data-price');
        }

        return null;
    }

    function applySelectedVatRate(row, option) {
        var vatSelect = qs('select[name="vat_rate[]"]', row);
        var vatRateValue = option.getAttribute('data-vat-rate');

        if (!vatSelect || vatRateValue === null || vatRateValue === '') {
            return;
        }

        var targetRate = parseFloat(vatRateValue);

        if (isNaN(targetRate)) {
            return;
        }

        for (var i = 0; i < vatSelect.options.length; i++) {
            if (parseFloat(vatSelect.options[i].value) === targetRate) {
                vatSelect.selectedIndex = i;
                return;
            }
        }
    }

    function defaultUnitDisplay(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);

        return (typeSelect && typeSelect.value === 'service') ? 'Service' : '-';
    }

    /* ---------------------------------------------------
       STOCK DISPLAY
       ---------------------------------------------------
       Shows the Product's data-stock value beside the Unit
       field. Never shown for Service items, never invented
       when the value is not present on the option.
    --------------------------------------------------- */

    function updateStockDisplay(row, option) {
        var stockField = qs('.dg-stock-note', row);

        if (!stockField) {
            return;
        }

        var typeSelect = qs('select[name="item_type[]"]', row);
        var isProduct = !!(typeSelect && typeSelect.value === 'product');

        if (!isProduct || !option || !option.hasAttribute('data-stock') || option.getAttribute('data-stock') === '') {
            stockField.textContent = '';
            return;
        }

        stockField.textContent = 'Stock: ' + option.getAttribute('data-stock');
    }

    /* ---------------------------------------------------
       QUANTITY STOCK VALIDATION
       ---------------------------------------------------
       Only applies when Item Type is Product and stock data
       is available on the selected option.
    --------------------------------------------------- */

    function getAvailableStock(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);

        if (!typeSelect || typeSelect.value !== 'product') {
            return null;
        }

        var itemSelect = getActiveItemSelect(row);
        var option = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;

        if (!option || !option.hasAttribute('data-stock') || option.getAttribute('data-stock') === '') {
            return null;
        }

        return toNumber(option.getAttribute('data-stock'));
    }

    function validateQuantityStock(row) {
        var quantityField = qs('input[name="quantity[]"]', row);

        if (!quantityField) {
            return true;
        }

        var stock = getAvailableStock(row);

        if (stock === null) {
            clearInvalid(quantityField);
            return true;
        }

        var quantity = toNumber(quantityField.value);

        if (quantity > stock) {
            markInvalid(quantityField);
            return false;
        }

        clearInvalid(quantityField);
        return true;
    }

    function applySelectedItemData(row) {
        var itemSelect = getActiveItemSelect(row);
        var priceField = qs('input[name="unit_price[]"]', row);
        var quantityField = qs('input[name="quantity[]"]', row);
        var unitField = qs('.dg-unit-display', row);

        if (!itemSelect) {
            if (unitField) {
                unitField.value = defaultUnitDisplay(row);
            }

            if (quantityField) {
                quantityField.removeAttribute('max');
            }

            updateStockDisplay(row, null);
            validateQuantityStock(row);
            syncItemIdFields(row);
            return;
        }

        var option = itemSelect.options[itemSelect.selectedIndex];

        if (!option || !option.value) {
            if (unitField) {
                unitField.value = defaultUnitDisplay(row);
            }

            if (quantityField) {
                quantityField.removeAttribute('max');
            }

            updateStockDisplay(row, null);
            validateQuantityStock(row);
            syncItemIdFields(row);
            return;
        }

        if (unitField) {
            unitField.value = option.getAttribute('data-unit') || defaultUnitDisplay(row);
        }

        if (priceField) {
            var sellingPrice = resolveSellingPrice(option);

            if (sellingPrice !== null && sellingPrice !== '') {
                priceField.value = sellingPrice;
            }
        }

        if (quantityField) {
            if (option.hasAttribute('data-stock')) {
                quantityField.setAttribute('max', option.getAttribute('data-stock'));
            } else {
                quantityField.removeAttribute('max');
            }
        }

        applySelectedVatRate(row, option);
        updateStockDisplay(row, option);
        validateQuantityStock(row);
        syncItemIdFields(row);
    }

    /* ---------------------------------------------------
       PRICE TYPE CHANGE
       ---------------------------------------------------
       Re-applies the correct Retail / Wholesale price to
       every row that already has a Product selected.
    --------------------------------------------------- */

    function refreshPricesForPriceType() {
        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var typeSelect = qs('select[name="item_type[]"]', row);
            var productSelect = getProductSelect(row);

            if (typeSelect && typeSelect.value === 'product' && productSelect && productSelect.value) {
                applySelectedItemData(row);
                recalcRow(row);
            }
        });
    }

    /* ---------------------------------------------------
       QUANTITY / PRICE / VAT / LINE TOTAL CALCULATION
    --------------------------------------------------- */

    function recalcRow(row) {
        var quantityField = qs('input[name="quantity[]"]', row);
        var priceField = qs('input[name="unit_price[]"]', row);
        var vatSelect = qs('select[name="vat_rate[]"]', row);
        var vatAmountField = qs('input[name="vat_amount[]"]', row);
        var totalPriceField = qs('input[name="total_price[]"]', row);

        var quantity = toNumber(quantityField ? quantityField.value : 0);
        var price = toNumber(priceField ? priceField.value : 0);
        var vatRate = toNumber(vatSelect ? vatSelect.value : 0);

        var lineAmount = quantity * price;
        var vatAmount = lineAmount * (vatRate / 100);
        var totalPrice = lineAmount + vatAmount;

        if (vatAmountField) {
            vatAmountField.value = toMoney(vatAmount);
        }

        if (totalPriceField) {
            totalPriceField.value = toMoney(totalPrice);
        }

        validateQuantityStock(row);
        recalcSummary();
    }

    /* ---------------------------------------------------
       SUBTOTAL / DISCOUNT / TAXABLE AMOUNT / TOTAL VAT /
       GRAND TOTAL / PAID AMOUNT / DUE AMOUNT CALCULATION
    --------------------------------------------------- */

    function calculateSubtotal() {
        var subtotal = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var quantityField = qs('input[name="quantity[]"]', row);
            var priceField = qs('input[name="unit_price[]"]', row);

            var quantity = toNumber(quantityField ? quantityField.value : 0);
            var price = toNumber(priceField ? priceField.value : 0);

            subtotal += quantity * price;
        });

        return subtotal;
    }

    function calculateTotalVat() {
        var totalVat = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var vatAmountField = qs('input[name="vat_amount[]"]', row);

            totalVat += toNumber(vatAmountField ? vatAmountField.value : 0);
        });

        return totalVat;
    }

    /* ---------------------------------------------------
       DISCOUNT VALIDATION
       ---------------------------------------------------
       Discount can never exceed the current Subtotal.
       An empty field is always treated as 0.00.
    --------------------------------------------------- */

    function clampDiscountToSubtotal(subtotal) {
        var discountField = qs('#discount_amount');

        if (!discountField) {
            return;
        }

        if (discountField.value === '') {
            clearInvalid(discountField);
            return;
        }

        var discount = toNumber(discountField.value);

        if (discount > subtotal) {
            discountField.value = toMoney(subtotal);
            markInvalid(discountField);
            showAlert('Discount cannot exceed the Subtotal amount (' + toMoney(subtotal) + ').', 'warning');
            return;
        }

        clearInvalid(discountField);
    }

    function recalcSummary() {
        var subtotal = calculateSubtotal();
        var totalVat = calculateTotalVat();

        clampDiscountToSubtotal(subtotal);

        var discountField = qs('#discount_amount');
        var discount = toNumber(discountField ? discountField.value : 0);
        var taxableAmount = Math.max(0, subtotal - discount);
        var grandTotal = taxableAmount + totalVat;

        var paidAmount = toNumber(qs('#paid_amount') ? qs('#paid_amount').value : 0);
        var dueAmount = Math.max(0, grandTotal - paidAmount);

        setValue('#subtotal', toMoney(subtotal));
        setValue('#taxable_amount', toMoney(taxableAmount));
        setValue('#total_vat', toMoney(totalVat));
        setValue('#grand_total', toMoney(grandTotal));
        setValue('#summary_paid_amount', toMoney(paidAmount));
        setValue('#due_amount', toMoney(dueAmount));
    }

    /* ---------------------------------------------------
       CUSTOMER SELECTION / CUSTOMER BALANCE DISPLAY
       ---------------------------------------------------
       Reads an optional data-balance attribute from the
       selected <option>. Falls back to 0.00 when absent.
    --------------------------------------------------- */

    function updateCustomerBalance() {
        var select = qs('#customer_id');

        if (!select || !select.parentElement) {
            return;
        }

        var note = qs('.dg-note', select.parentElement);
        var option = select.options[select.selectedIndex];
        var balance = (option && option.hasAttribute('data-balance'))
            ? toMoney(option.getAttribute('data-balance'))
            : '0.00';

        if (note) {
            note.textContent = 'Customer Balance: ' + balance;
        }
    }

    /* ---------------------------------------------------
       PAYMENT ACCOUNT SELECTION / ACCOUNT BALANCE DISPLAY
    --------------------------------------------------- */

    function updateAccountBalance() {
        var select = qs('#account_id');

        if (!select || !select.parentElement) {
            return;
        }

        var note = qs('.dg-note', select.parentElement);
        var option = select.options[select.selectedIndex];
        var balance = (option && option.hasAttribute('data-balance'))
            ? toMoney(option.getAttribute('data-balance'))
            : '0.00';

        if (note) {
            note.textContent = 'Account Balance: ' + balance;
        }
    }

    /* ---------------------------------------------------
       BARCODE SEARCH
       ---------------------------------------------------
       Matches the entered code against an optional
       data-barcode attribute on Product options only.
       Shows a dg-alert when nothing matches.
    --------------------------------------------------- */

    function isRowItemEmpty(row) {
        var typeSelect = qs('select[name="item_type[]"]', row);
        var type = typeSelect ? typeSelect.value : '';

        if (!type) {
            return true;
        }

        var activeSelect = getActiveItemSelect(row);

        return !activeSelect || !activeSelect.value;
    }

    function findEmptyRow() {
        var rows = qsa('tr.dg-row', itemsBody);

        for (var i = 0; i < rows.length; i++) {
            if (isRowItemEmpty(rows[i])) {
                return rows[i];
            }
        }

        return null;
    }

    function findProductOptionByBarcode(code) {
        var options = qsa('select.dg-product-select option[data-barcode]', itemsBody);

        for (var i = 0; i < options.length; i++) {
            var barcode = options[i].getAttribute('data-barcode');

            if (barcode && barcode.toLowerCase() === code.toLowerCase()) {
                return options[i];
            }
        }

        return null;
    }

    function showAlert(message, type) {
        var container = qs('.dg-container .container-fluid');

        if (!container) {
            return;
        }

        var existing = qs('.dg-alert.dg-js-alert');

        if (existing) {
            existing.parentNode.removeChild(existing);
        }

        var alertBox = document.createElement('div');
        alertBox.className = 'alert alert-' + (type || 'warning') + ' dg-alert dg-js-alert';
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = message;

        container.insertBefore(alertBox, container.firstChild);
    }

    function searchBarcode() {
        var barcodeField = qs('#barcode');

        if (!barcodeField) {
            return;
        }

        var code = barcodeField.value.trim();

        if (!code) {
            return;
        }

        var matchedOption = findProductOptionByBarcode(code);

        if (!matchedOption) {
            showAlert('No product found for barcode "' + code + '".', 'warning');
            return;
        }

        var targetRow = findEmptyRow() || addRow();
        var typeSelect = qs('select[name="item_type[]"]', targetRow);
        var productSelect = getProductSelect(targetRow);

        if (typeSelect) {
            typeSelect.value = 'product';
        }

        updateItemSelectsByType(targetRow);

        if (productSelect) {
            productSelect.value = matchedOption.value;
        }

        applySelectedItemData(targetRow);
        syncItemIdFields(targetRow);
        recalcRow(targetRow);

        barcodeField.value = '';

        var quantityField = qs('input[name="quantity[]"]', targetRow);

        if (quantityField) {
            quantityField.focus();
        }
    }

    /* ---------------------------------------------------
       INPUT VALIDATION / FORM SUBMISSION VALIDATION
       ---------------------------------------------------
       Mirrors the validation rules already enforced in
       SalesController@store so problems are caught before
       the request is sent to the server.
    --------------------------------------------------- */

    function markInvalid(field) {
        if (field) {
            field.classList.add('is-invalid');
        }
    }

    function clearInvalid(field) {
        if (field) {
            field.classList.remove('is-invalid');
        }
    }

    function clearAllInvalid() {
        qsa('.is-invalid', form).forEach(function (field) {
            field.classList.remove('is-invalid');
        });
    }

    function validateForm() {
        var errors = [];
        var firstInvalid = null;

        clearAllInvalid();

        var customerField = qs('#customer_id');

        if (!customerField || !customerField.value) {
            errors.push('Please select a customer.');
            markInvalid(customerField);
            firstInvalid = firstInvalid || customerField;
        }

        var saleDateField = qs('#sale_date');

        if (!saleDateField || !saleDateField.value) {
            errors.push('Please select the sale date.');
            markInvalid(saleDateField);
            firstInvalid = firstInvalid || saleDateField;
        }

        var validRowCount = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var typeSelect = qs('select[name="item_type[]"]', row);
            var productSelect = getProductSelect(row);
            var serviceSelect = getServiceSelect(row);
            var quantityField = qs('input[name="quantity[]"]', row);
            var priceField = qs('input[name="unit_price[]"]', row);

            syncItemIdFields(row);

            var hasType = !!(typeSelect && typeSelect.value);
            var hasItem = false;
            var itemSelect = null;

            if (hasType && typeSelect.value === 'product') {
                itemSelect = productSelect;
                hasItem = !!(productSelect && productSelect.value);
            } else if (hasType && typeSelect.value === 'service') {
                itemSelect = serviceSelect;
                hasItem = !!(serviceSelect && serviceSelect.value);
            }
            var hasQuantity = !!(quantityField && toNumber(quantityField.value) > 0);
            var hasPrice = !!(priceField && priceField.value !== '' && toNumber(priceField.value) >= 0);

            var rowTouched = hasType || hasItem || hasQuantity || (priceField && priceField.value !== '');

            if (!rowTouched) {
                return;
            }

            if (!hasType) {
                markInvalid(typeSelect);
                firstInvalid = firstInvalid || typeSelect;
            }

            if (!hasItem) {
                markInvalid(itemSelect);
                firstInvalid = firstInvalid || itemSelect;
            }

            if (!hasQuantity) {
                markInvalid(quantityField);
                firstInvalid = firstInvalid || quantityField;
            } else if (!validateQuantityStock(row)) {
                errors.push('Quantity exceeds available stock.');
                firstInvalid = firstInvalid || quantityField;
            }

            if (!hasPrice) {
                markInvalid(priceField);
                firstInvalid = firstInvalid || priceField;
            }

            if (hasType && hasItem && hasQuantity && hasPrice) {
                validRowCount++;
            }
        });

        if (validRowCount === 0) {
            errors.push('Please add at least one valid item.');
        }

        var paidField = qs('#paid_amount');
        var accountField = qs('#account_id');
        var paidAmount = toNumber(paidField ? paidField.value : 0);

        if (paidAmount > 0 && (!accountField || !accountField.value)) {
            errors.push('Select payment account.');
            markInvalid(accountField);
            firstInvalid = firstInvalid || accountField;
        }

        if (errors.length) {
            showAlert(errors.join(' '), 'danger');

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return false;
        }

        return true;
    }

    /* ---------------------------------------------------
       KEYBOARD NAVIGATION
       ---------------------------------------------------
       Enter moves to the next field, then the next row,
       adding a new row automatically at the end of the
       table for continuous, spreadsheet-style entry.
    --------------------------------------------------- */

    var FOCUSABLE_SELECTOR = 'input:not([readonly]):not([type="hidden"]), select';

    function focusableInRow(row) {
        return qsa(FOCUSABLE_SELECTOR, row);
    }

    function handleRowKeydown(event) {
        if (event.key !== 'Enter') {
            return;
        }

        var target = event.target;
        var row = target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        event.preventDefault();

        var fields = focusableInRow(row);
        var currentIndex = fields.indexOf(target);

        if (currentIndex > -1 && currentIndex < fields.length - 1) {
            fields[currentIndex + 1].focus();
            return;
        }

        var nextRow = row.nextElementSibling;

        if (nextRow) {
            var nextFields = focusableInRow(nextRow);

            if (nextFields.length) {
                nextFields[0].focus();
                return;
            }
        }

        var newRow = addRow();
        var newFields = focusableInRow(newRow);

        if (newFields.length) {
            newFields[0].focus();
        }
    }

    /* ---------------------------------------------------
       EVENT BINDING
    --------------------------------------------------- */

    function onItemsBodyClick(event) {
        var deleteBtn = event.target.closest('button.btn-outline-danger');

        if (deleteBtn) {
            var row = deleteBtn.closest('tr.dg-row');

            if (row) {
                deleteRow(row);
            }
        }
    }

    function onItemsBodyChange(event) {
        var row = event.target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        if (event.target.matches('select[name="item_type[]"]')) {
            updateItemSelectsByType(row);
            recalcRow(row);
            return;
        }

        if (event.target.matches('select.dg-product-select, select.dg-service-select')) {
            applySelectedItemData(row);
            syncItemIdFields(row);
            recalcRow(row);
            return;
        }

        if (event.target.matches('select[name="vat_rate[]"]')) {
            recalcRow(row);
        }
    }

    function onItemsBodyInput(event) {
        var row = event.target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        if (event.target.matches('input[name="quantity[]"], input[name="unit_price[]"]')) {
            clearInvalid(event.target);
            recalcRow(row);
        }
    }

    function onItemsBodyFocusOut(event) {
        var target = event.target;

        if (target.matches('input[name="quantity[]"]') && target.value !== '' && toNumber(target.value) < 1) {
            target.value = '1';
        }

        if (target.matches('input[name="unit_price[]"]') && target.value !== '' && toNumber(target.value) < 0) {
            target.value = '0';
        }
    }

    function bindAddItemButton() {
        var addItemButton = qs('.dg-add-item', form);

        if (!addItemButton) {
            return;
        }

        addItemButton.addEventListener('click', function () {
            var row = addRow();
            var fields = focusableInRow(row);

            if (fields.length) {
                fields[0].focus();
            }

            recalcSummary();
        });
    }

    function bindPriceType() {
        var priceTypeField = qs('#price_type');

        if (priceTypeField) {
            priceTypeField.addEventListener('change', refreshPricesForPriceType);
        }
    }

    function bindCustomerAndAccount() {
        var customerField = qs('#customer_id');
        var accountField = qs('#account_id');

        if (customerField) {
            customerField.addEventListener('change', function () {
                clearInvalid(customerField);
                updateCustomerBalance();
            });
        }

        if (accountField) {
            accountField.addEventListener('change', function () {
                clearInvalid(accountField);
                updateAccountBalance();
            });
        }
    }

    function bindPaidAmount() {
        var paidField = qs('#paid_amount');

        if (paidField) {
            paidField.addEventListener('input', function () {
                clearInvalid(paidField);
                recalcSummary();
            });
        }
    }

    function bindDiscount() {
        var discountField = qs('#discount_amount');

        if (!discountField) {
            return;
        }

        discountField.addEventListener('input', function () {
            recalcSummary();
        });

        discountField.addEventListener('focusout', function () {
            if (discountField.value === '') {
                discountField.value = '0.00';
            }

            recalcSummary();
        });
    }

    function bindBarcode() {
        var barcodeField = qs('#barcode');

        if (!barcodeField) {
            return;
        }

        barcodeField.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchBarcode();
            }
        });
    }

    function bindSaleDate() {
        var saleDateField = qs('#sale_date');

        if (saleDateField) {
            saleDateField.addEventListener('change', function () {
                clearInvalid(saleDateField);
            });
        }
    }

    function bindItemsBody() {
        itemsBody.addEventListener('click', onItemsBodyClick);
        itemsBody.addEventListener('change', onItemsBodyChange);
        itemsBody.addEventListener('input', onItemsBodyInput);
        itemsBody.addEventListener('focusout', onItemsBodyFocusOut);
        itemsBody.addEventListener('keydown', handleRowKeydown);
    }

    function bindFormSubmit() {
        form.addEventListener('submit', function (event) {
            recalcSummary();
            syncAllItemIdFields();

            if (!validateForm()) {
                event.preventDefault();
            }
        });
    }

    /* ---------------------------------------------------
       INIT
    --------------------------------------------------- */

    function isSalesBillingScreen() {
        return !!(form
            && itemsBody
            && qs('#customer_id')
            && qs('select.dg-product-select', itemsBody)
            && qs('select.dg-service-select', itemsBody));
    }

    function init() {
        form = document.getElementById('dgForm');

        if (!form) {
            return;
        }

        itemsBody = qs('table.dg-table tbody.dg-body', form);

        if (!itemsBody || !qs('tr.dg-row', itemsBody) || !isSalesBillingScreen()) {
            return;
        }

        rowTemplate = qs('tr.dg-row', itemsBody).cloneNode(true);

        bindItemsBody();
        bindAddItemButton();
        bindPriceType();
        bindCustomerAndAccount();
        bindPaidAmount();
        bindDiscount();
        bindBarcode();
        bindSaleDate();
        bindFormSubmit();

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            updateItemSelectsByType(row);
        });

        renumberRows();
        recalcSummary();
        updateCustomerBalance();
        updateAccountBalance();

        var customerField = qs('#customer_id');

        if (customerField) {
            customerField.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init,
        addRow: addRow,
        recalcSummary: recalcSummary
    };

})();

/* =========================================================
   MODULE : PURCHASE BILLING (Create Purchase Invoice)
   Screen : resources/views/company/purchases/create.blade.php
   ========================================================= */

DG.purchaseBilling = (function () {

    'use strict';

    var form = null;
    var itemsBody = null;
    var rowTemplate = null;

    function toNumber(value) {
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function toMoney(value) {
        return toNumber(value).toFixed(2);
    }

    function qs(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function qsa(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function setValue(selector, value) {
        var field = qs(selector);

        if (field) {
            field.value = value;
        }
    }

    function renumberRows() {
        qsa('tr.dg-row', itemsBody).forEach(function (row, index) {
            var numberCell = row.children[0];

            if (numberCell) {
                numberCell.textContent = index + 1;
            }

            var deleteBtn = qs('button.btn-outline-danger', row);

            if (deleteBtn) {
                deleteBtn.setAttribute('aria-label', 'Delete row ' + (index + 1));
            }
        });
    }

    function nextRowIndex() {
        return qsa('tr.dg-row', itemsBody).length;
    }

    function reindexClone(clone, newIndex) {
        var deleteBtn = qs('button.btn-outline-danger', clone);

        if (deleteBtn) {
            deleteBtn.setAttribute('aria-label', 'Delete row ' + (newIndex + 1));
        }
    }

    function resetClone(clone) {
        qsa('select', clone).forEach(function (select) {
            select.selectedIndex = 0;
            select.classList.remove('is-invalid');
        });

        qsa('input[type="hidden"]', clone).forEach(function (input) {
            if (input.classList.contains('dg-item-type')) {
                input.value = 'product';
            } else {
                input.value = '';
            }
        });

        qsa('input[type="number"]', clone).forEach(function (input) {
            input.value = input.readOnly ? '0' : '';
            input.classList.remove('is-invalid');
        });

        qsa('input[type="text"]', clone).forEach(function (input) {
            if (input.readOnly) {
                input.value = '-';
            }
        });

        var stockNote = qs('.dg-stock-note', clone);

        if (stockNote) {
            stockNote.textContent = '';
        }
    }

    function addRow() {
        var newIndex = nextRowIndex();
        var clone = rowTemplate.cloneNode(true);

        reindexClone(clone, newIndex);
        resetClone(clone);
        itemsBody.appendChild(clone);
        renumberRows();

        return clone;
    }

    function deleteRow(row) {
        var rows = qsa('tr.dg-row', itemsBody);

        if (rows.length <= 1) {
            resetClone(row);
            syncItemIdFields(row);
            recalcRow(row);
            return;
        }

        row.parentNode.removeChild(row);
        renumberRows();
        recalcSummary();
    }

    function getItemSelect(row) {
        return qs('select.dg-item-select', row);
    }

    function syncItemIdFields(row) {
        var itemSelect = getItemSelect(row);
        var typeField = qs('input.dg-item-type', row);
        var productField = qs('input.dg-product-id', row);
        var serviceField = qs('input.dg-service-id', row);

        if (!productField || !serviceField) {
            return;
        }

        productField.value = '';
        serviceField.value = '';

        if (!itemSelect || !itemSelect.value) {
            if (typeField) {
                typeField.value = 'product';
            }
            return;
        }

        var option = itemSelect.options[itemSelect.selectedIndex];
        var type = option.getAttribute('data-item-type') || 'product';

        if (typeField) {
            typeField.value = type;
        }

        if (type === 'product') {
            productField.value = option.getAttribute('data-product-id') || '';
            return;
        }

        if (type === 'service') {
            serviceField.value = option.getAttribute('data-service-id') || '';
        }
    }

    function syncAllItemIdFields() {
        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            syncItemIdFields(row);
        });
    }

    function resolveUnitCost(option) {
        if (option.hasAttribute('data-cost-price')) {
            return option.getAttribute('data-cost-price');
        }

        if (option.hasAttribute('data-price')) {
            return option.getAttribute('data-price');
        }

        return null;
    }

    function applySelectedVatRate(row, option) {
        var vatSelect = qs('select[name="vat_rate[]"]', row);
        var vatRateValue = option.getAttribute('data-vat-rate');

        if (!vatSelect || vatRateValue === null || vatRateValue === '') {
            return;
        }

        var targetRate = parseFloat(vatRateValue);

        if (isNaN(targetRate)) {
            return;
        }

        for (var i = 0; i < vatSelect.options.length; i++) {
            if (parseFloat(vatSelect.options[i].value) === targetRate) {
                vatSelect.selectedIndex = i;
                return;
            }
        }
    }

    function defaultUnitDisplay(option) {
        if (!option || !option.value) {
            return '-';
        }

        if ((option.getAttribute('data-item-type') || '') === 'service') {
            return 'Service';
        }

        return option.getAttribute('data-unit') || '-';
    }

    function updateStockDisplay(row, option) {
        var stockField = qs('.dg-stock-note', row);

        if (!stockField) {
            return;
        }

        if (
            !option
            || !option.value
            || (option.getAttribute('data-item-type') || '') !== 'product'
            || !option.hasAttribute('data-stock')
            || option.getAttribute('data-stock') === ''
        ) {
            stockField.textContent = '';
            return;
        }

        stockField.textContent = 'Stock: ' + option.getAttribute('data-stock');
    }

    function applySelectedItemData(row) {
        var itemSelect = getItemSelect(row);
        var priceField = qs('input[name="unit_price[]"]', row);
        var unitField = qs('.dg-unit-display', row);

        if (!itemSelect || !itemSelect.value) {
            if (unitField) {
                unitField.value = '-';
            }

            updateStockDisplay(row, null);
            syncItemIdFields(row);
            return;
        }

        var option = itemSelect.options[itemSelect.selectedIndex];

        if (unitField) {
            unitField.value = defaultUnitDisplay(option);
        }

        if (priceField) {
            var unitCost = resolveUnitCost(option);

            if (unitCost !== null && unitCost !== '') {
                priceField.value = unitCost;
            }
        }

        applySelectedVatRate(row, option);
        updateStockDisplay(row, option);
        syncItemIdFields(row);
    }

    function recalcRow(row) {
        var quantityField = qs('input[name="quantity[]"]', row);
        var priceField = qs('input[name="unit_price[]"]', row);
        var vatSelect = qs('select[name="vat_rate[]"]', row);
        var vatAmountField = qs('input[name="vat_amount[]"]', row);
        var totalPriceField = qs('input[name="total_price[]"]', row);

        var quantity = toNumber(quantityField ? quantityField.value : 0);
        var price = toNumber(priceField ? priceField.value : 0);
        var vatRate = toNumber(vatSelect ? vatSelect.value : 0);

        var lineAmount = quantity * price;
        var vatAmount = lineAmount * (vatRate / 100);
        var totalPrice = lineAmount + vatAmount;

        if (vatAmountField) {
            vatAmountField.value = toMoney(vatAmount);
        }

        if (totalPriceField) {
            totalPriceField.value = toMoney(totalPrice);
        }

        recalcSummary();
    }

    function calculateSubtotal() {
        var subtotal = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var quantityField = qs('input[name="quantity[]"]', row);
            var priceField = qs('input[name="unit_price[]"]', row);
            var quantity = toNumber(quantityField ? quantityField.value : 0);
            var price = toNumber(priceField ? priceField.value : 0);

            subtotal += quantity * price;
        });

        return subtotal;
    }

    function calculateTotalVat() {
        var totalVat = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var vatAmountField = qs('input[name="vat_amount[]"]', row);

            totalVat += toNumber(vatAmountField ? vatAmountField.value : 0);
        });

        return totalVat;
    }

    function clampDiscountToGrossTotal(grossTotal) {
        var discountField = qs('#discount_amount');

        if (!discountField) {
            return;
        }

        if (discountField.value === '') {
            clearInvalid(discountField);
            return;
        }

        var discount = toNumber(discountField.value);

        if (discount > grossTotal) {
            discountField.value = toMoney(grossTotal);
            markInvalid(discountField);
            showAlert('Discount cannot exceed the gross total amount (' + toMoney(grossTotal) + ').', 'warning');
            return;
        }

        clearInvalid(discountField);
    }

    function recalcSummary() {
        var subtotal = calculateSubtotal();
        var totalVat = calculateTotalVat();
        var grossTotal = subtotal + totalVat;

        clampDiscountToGrossTotal(grossTotal);

        var discountField = qs('#discount_amount');
        var discount = toNumber(discountField ? discountField.value : 0);
        var taxableAmount = Math.max(0, subtotal - discount);
        var grandTotal = Math.max(0, grossTotal - discount);
        var paidAmount = toNumber(qs('#paid_amount') ? qs('#paid_amount').value : 0);
        var dueAmount = Math.max(0, grandTotal - paidAmount);

        setValue('#subtotal', toMoney(subtotal));
        setValue('#taxable_amount', toMoney(taxableAmount));
        setValue('#total_vat', toMoney(totalVat));
        setValue('#grand_total', toMoney(grandTotal));
        setValue('#summary_paid_amount', toMoney(paidAmount));
        setValue('#due_amount', toMoney(dueAmount));
    }

    function updateSupplierBalance() {
        var select = qs('#supplier_id');

        if (!select || !select.parentElement) {
            return;
        }

        var note = qs('.dg-note', select.parentElement);
        var option = select.options[select.selectedIndex];
        var balance = (option && option.hasAttribute('data-balance'))
            ? toMoney(option.getAttribute('data-balance'))
            : '0.00';

        if (note) {
            note.textContent = 'Supplier Balance: ' + balance;
        }
    }

    function updateAccountBalance() {
        var select = qs('#account_id');

        if (!select || !select.parentElement) {
            return;
        }

        var note = qs('.dg-note', select.parentElement);
        var option = select.options[select.selectedIndex];
        var balance = (option && option.hasAttribute('data-balance'))
            ? toMoney(option.getAttribute('data-balance'))
            : '0.00';

        if (note) {
            note.textContent = 'Account Balance: ' + balance;
        }
    }

    function isRowItemEmpty(row) {
        var itemSelect = getItemSelect(row);
        return !itemSelect || !itemSelect.value;
    }

    function findEmptyRow() {
        var rows = qsa('tr.dg-row', itemsBody);

        for (var i = 0; i < rows.length; i++) {
            if (isRowItemEmpty(rows[i])) {
                return rows[i];
            }
        }

        return null;
    }

    function findProductOptionByBarcode(code) {
        var options = qsa('select.dg-item-select option[data-barcode]', itemsBody);

        for (var i = 0; i < options.length; i++) {
            var barcode = options[i].getAttribute('data-barcode');

            if (barcode && barcode.toLowerCase() === code.toLowerCase()) {
                return options[i];
            }
        }

        return null;
    }

    function showAlert(message, type) {
        var container = qs('.dg-container .container-fluid');

        if (!container) {
            return;
        }

        var existing = qs('.dg-alert.dg-js-alert');

        if (existing) {
            existing.parentNode.removeChild(existing);
        }

        var alertBox = document.createElement('div');
        alertBox.className = 'alert alert-' + (type || 'warning') + ' dg-alert dg-js-alert';
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = message;

        container.insertBefore(alertBox, container.firstChild);
    }

    function searchBarcode() {
        var barcodeField = qs('#barcode');

        if (!barcodeField) {
            return;
        }

        var code = barcodeField.value.trim();

        if (!code) {
            return;
        }

        var matchedOption = findProductOptionByBarcode(code);

        if (!matchedOption) {
            showAlert('No product found for barcode "' + code + '".', 'warning');
            return;
        }

        var targetRow = findEmptyRow() || addRow();
        var itemSelect = getItemSelect(targetRow);

        if (itemSelect) {
            itemSelect.value = matchedOption.value;
        }

        applySelectedItemData(targetRow);
        syncItemIdFields(targetRow);
        recalcRow(targetRow);

        barcodeField.value = '';

        var quantityField = qs('input[name="quantity[]"]', targetRow);

        if (quantityField) {
            quantityField.focus();
        }
    }

    function markInvalid(field) {
        if (field) {
            field.classList.add('is-invalid');
        }
    }

    function clearInvalid(field) {
        if (field) {
            field.classList.remove('is-invalid');
        }
    }

    function clearAllInvalid() {
        qsa('.is-invalid', form).forEach(function (field) {
            field.classList.remove('is-invalid');
        });
    }

    function validateForm() {
        var errors = [];
        var firstInvalid = null;

        clearAllInvalid();

        var supplierField = qs('#supplier_id');

        if (!supplierField || !supplierField.value) {
            errors.push('Please select a supplier.');
            markInvalid(supplierField);
            firstInvalid = firstInvalid || supplierField;
        }

        var purchaseDateField = qs('#purchase_date');

        if (!purchaseDateField || !purchaseDateField.value) {
            errors.push('Please select the purchase date.');
            markInvalid(purchaseDateField);
            firstInvalid = firstInvalid || purchaseDateField;
        }

        var validRowCount = 0;

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            var itemSelect = getItemSelect(row);
            var quantityField = qs('input[name="quantity[]"]', row);
            var priceField = qs('input[name="unit_price[]"]', row);

            syncItemIdFields(row);

            var hasItem = !!(itemSelect && itemSelect.value);
            var hasQuantity = !!(quantityField && toNumber(quantityField.value) > 0);
            var hasPrice = !!(priceField && priceField.value !== '' && toNumber(priceField.value) >= 0);
            var rowTouched = hasItem || hasQuantity || (priceField && priceField.value !== '');

            if (!rowTouched) {
                return;
            }

            if (!hasItem) {
                markInvalid(itemSelect);
                firstInvalid = firstInvalid || itemSelect;
            }

            if (!hasQuantity) {
                markInvalid(quantityField);
                firstInvalid = firstInvalid || quantityField;
            }

            if (!hasPrice) {
                markInvalid(priceField);
                firstInvalid = firstInvalid || priceField;
            }

            if (hasItem && hasQuantity && hasPrice) {
                validRowCount++;
            }
        });

        if (validRowCount === 0) {
            errors.push('Please add at least one valid item.');
        }

        var paidField = qs('#paid_amount');
        var accountField = qs('#account_id');
        var paidAmount = toNumber(paidField ? paidField.value : 0);

        if (paidAmount > 0 && (!accountField || !accountField.value)) {
            errors.push('Select payment account.');
            markInvalid(accountField);
            firstInvalid = firstInvalid || accountField;
        }

        if (errors.length) {
            showAlert(errors.join(' '), 'danger');

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return false;
        }

        return true;
    }

    var FOCUSABLE_SELECTOR = 'input:not([readonly]):not([type="hidden"]), select';

    function focusableInRow(row) {
        return qsa(FOCUSABLE_SELECTOR, row);
    }

    function handleRowKeydown(event) {
        if (event.key !== 'Enter') {
            return;
        }

        var target = event.target;
        var row = target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        event.preventDefault();

        var fields = focusableInRow(row);
        var currentIndex = fields.indexOf(target);

        if (currentIndex > -1 && currentIndex < fields.length - 1) {
            fields[currentIndex + 1].focus();
            return;
        }

        var nextRow = row.nextElementSibling;

        if (nextRow) {
            var nextFields = focusableInRow(nextRow);

            if (nextFields.length) {
                nextFields[0].focus();
                return;
            }
        }

        var newRow = addRow();
        var newFields = focusableInRow(newRow);

        if (newFields.length) {
            newFields[0].focus();
        }
    }

    function onItemsBodyClick(event) {
        var deleteBtn = event.target.closest('button.btn-outline-danger');

        if (deleteBtn) {
            var row = deleteBtn.closest('tr.dg-row');

            if (row) {
                deleteRow(row);
            }
        }
    }

    function onItemsBodyChange(event) {
        var row = event.target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        if (event.target.matches('select.dg-item-select')) {
            applySelectedItemData(row);
            syncItemIdFields(row);
            recalcRow(row);
            return;
        }

        if (event.target.matches('select[name="vat_rate[]"]')) {
            recalcRow(row);
        }
    }

    function onItemsBodyInput(event) {
        var row = event.target.closest('tr.dg-row');

        if (!row) {
            return;
        }

        if (event.target.matches('input[name="quantity[]"], input[name="unit_price[]"]')) {
            clearInvalid(event.target);
            recalcRow(row);
        }
    }

    function onItemsBodyFocusOut(event) {
        var target = event.target;

        if (target.matches('input[name="quantity[]"]') && target.value !== '' && toNumber(target.value) < 1) {
            target.value = '1';
        }

        if (target.matches('input[name="unit_price[]"]') && target.value !== '' && toNumber(target.value) < 0) {
            target.value = '0';
        }
    }

    function bindAddItemButton() {
        var addItemButton = qs('.dg-add-item', form);

        if (!addItemButton) {
            return;
        }

        addItemButton.addEventListener('click', function () {
            var row = addRow();
            var fields = focusableInRow(row);

            if (fields.length) {
                fields[0].focus();
            }

            recalcSummary();
        });
    }

    function bindSupplierAndAccount() {
        var supplierField = qs('#supplier_id');
        var accountField = qs('#account_id');

        if (supplierField) {
            supplierField.addEventListener('change', function () {
                clearInvalid(supplierField);
                updateSupplierBalance();
            });
        }

        if (accountField) {
            accountField.addEventListener('change', function () {
                clearInvalid(accountField);
                updateAccountBalance();
            });
        }
    }

    function bindPaidAmount() {
        var paidField = qs('#paid_amount');

        if (paidField) {
            paidField.addEventListener('input', function () {
                clearInvalid(paidField);
                recalcSummary();
            });
        }
    }

    function bindDiscount() {
        var discountField = qs('#discount_amount');

        if (!discountField) {
            return;
        }

        discountField.addEventListener('input', function () {
            recalcSummary();
        });

        discountField.addEventListener('focusout', function () {
            if (discountField.value === '') {
                discountField.value = '0.00';
            }

            recalcSummary();
        });
    }

    function bindBarcode() {
        var barcodeField = qs('#barcode');

        if (!barcodeField) {
            return;
        }

        barcodeField.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchBarcode();
            }
        });
    }

    function bindPurchaseDate() {
        var purchaseDateField = qs('#purchase_date');

        if (purchaseDateField) {
            purchaseDateField.addEventListener('change', function () {
                clearInvalid(purchaseDateField);
            });
        }
    }

    function bindItemsBody() {
        itemsBody.addEventListener('click', onItemsBodyClick);
        itemsBody.addEventListener('change', onItemsBodyChange);
        itemsBody.addEventListener('input', onItemsBodyInput);
        itemsBody.addEventListener('focusout', onItemsBodyFocusOut);
        itemsBody.addEventListener('keydown', handleRowKeydown);
    }

    function bindFormSubmit() {
        form.addEventListener('submit', function (event) {
            recalcSummary();
            syncAllItemIdFields();

            if (!validateForm()) {
                event.preventDefault();
            }
        });
    }

    function isPurchaseBillingScreen() {
        return !!(form
            && itemsBody
            && qs('#supplier_id')
            && qs('#purchase_date')
            && qs('select.dg-item-select', itemsBody));
    }

    function init() {
        form = document.getElementById('dgForm');

        if (!form) {
            return;
        }

        itemsBody = qs('table.dg-table tbody.dg-body', form);

        if (!itemsBody || !qs('tr.dg-row', itemsBody) || !isPurchaseBillingScreen()) {
            return;
        }

        rowTemplate = qs('tr.dg-row', itemsBody).cloneNode(true);

        bindItemsBody();
        bindAddItemButton();
        bindSupplierAndAccount();
        bindPaidAmount();
        bindDiscount();
        bindBarcode();
        bindPurchaseDate();
        bindFormSubmit();

        qsa('tr.dg-row', itemsBody).forEach(function (row) {
            applySelectedItemData(row);
            syncItemIdFields(row);
            recalcRow(row);
        });

        renumberRows();
        recalcSummary();
        updateSupplierBalance();
        updateAccountBalance();

        var supplierField = qs('#supplier_id');

        if (supplierField) {
            supplierField.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init,
        addRow: addRow,
        recalcSummary: recalcSummary
    };

})();

/* =========================================================
   MODULE : SALES PAYMENT (Receive Sales Payment)
   Screen : resources/views/company/sales-payment/create.blade.php
   ========================================================= */

DG.salesPayment = (function () {

    'use strict';

    var form = null;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function validateForm() {
        var paidField = qs('#paid_amount', form);
        var accountField = qs('#account_id', form);
        var remaining = toNumber(paidField ? paidField.getAttribute('data-remaining') : 0);
        var paidAmount = toNumber(paidField ? paidField.value : 0);

        if (paidAmount <= 0) {
            alert('Payment amount must be greater than zero.');
            if (paidField) {
                paidField.focus();
            }
            return false;
        }

        if (paidAmount > remaining) {
            alert('Payment amount cannot exceed remaining due amount.');
            if (paidField) {
                paidField.focus();
            }
            return false;
        }

        if (accountField && !accountField.value) {
            alert('Please select a receive account.');
            accountField.focus();
            return false;
        }

        return true;
    }

    function bindFormSubmit() {
        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
            }
        });
    }

    function init() {
        form = document.getElementById('dgSalesPaymentForm');

        if (!form) {
            return;
        }

        bindFormSubmit();
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init
    };

})();

/* =========================================================
   MODULE : PURCHASE PAYMENT (Make Purchase Payment)
   Screen : resources/views/company/purchase-payments/create.blade.php
   ========================================================= */

DG.purchasePayment = (function () {

    'use strict';

    var form = null;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function validateForm() {
        var paidField = qs('#paid_amount', form);
        var accountField = qs('#account_id', form);
        var remaining = toNumber(paidField ? paidField.getAttribute('data-remaining') : 0);
        var paidAmount = toNumber(paidField ? paidField.value : 0);

        if (paidAmount <= 0) {
            alert('Payment amount must be greater than zero.');
            if (paidField) {
                paidField.focus();
            }
            return false;
        }

        if (paidAmount > remaining) {
            alert('Payment amount cannot exceed remaining due amount.');
            if (paidField) {
                paidField.focus();
            }
            return false;
        }

        if (accountField && !accountField.value) {
            alert('Please select a payment account.');
            accountField.focus();
            return false;
        }

        return true;
    }

    function bindFormSubmit() {
        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
            }
        });
    }

    function init() {
        form = document.getElementById('dgPurchasePaymentForm');

        if (!form) {
            return;
        }

        bindFormSubmit();
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init
    };

})();

/* =========================================================
   MODULE : SALES RETURN
   Screen : resources/views/company/sales-return/create.blade.php
   ========================================================= */

DG.salesReturn = (function () {

    'use strict';

    var form = null;

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function calculateTotals() {
        var subtotalTotal = 0;
        var vatTotal = 0;
        var grandTotal = 0;
        var rows = form ? form.querySelectorAll('.dg-return-item-row') : [];

        rows.forEach(function (row) {
            var qtyField = row.querySelector('.return-qty');
            var priceField = row.querySelector('.unit-price');
            var vatField = row.querySelector('.vat-rate');
            var totalField = row.querySelector('.row-total');

            if (!qtyField || !priceField || !vatField || !totalField) {
                return;
            }

            var qty = toNumber(qtyField.value);
            var maxQty = toNumber(qtyField.getAttribute('data-available') || qtyField.max);

            if (qty < 0) {
                qty = 0;
                qtyField.value = 0;
            }

            if (qty > maxQty) {
                qty = maxQty;
                qtyField.value = maxQty;
            }

            var price = toNumber(priceField.value);
            var vatRate = toNumber(vatField.value);
            var subtotal = qty * price;
            var vatAmount = (subtotal * vatRate) / 100;
            var total = subtotal + vatAmount;

            totalField.value = total.toFixed(2);
            subtotalTotal += subtotal;
            vatTotal += vatAmount;
            grandTotal += total;
        });

        var subtotalEl = document.getElementById('subtotal');
        var vatEl = document.getElementById('totalVat');
        var grandEl = document.getElementById('grandTotal');

        if (subtotalEl) {
            subtotalEl.value = subtotalTotal.toFixed(2);
        }

        if (vatEl) {
            vatEl.value = vatTotal.toFixed(2);
        }

        if (grandEl) {
            grandEl.value = grandTotal.toFixed(2);
        }
    }

    function bindInputEvents() {
        document.addEventListener('input', function (event) {
            if (!form || !form.contains(event.target)) {
                return;
            }

            if (event.target.classList.contains('return-qty')) {
                calculateTotals();
            }
        });
    }

    function init() {
        form = document.getElementById('dgSalesReturnForm');

        if (!form) {
            return;
        }

        bindInputEvents();
        calculateTotals();
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init,
        calculateTotals: calculateTotals
    };

})();

/* =========================================================
   MODULE : PURCHASE RETURN
   Screen : resources/views/company/purchase-return/create.blade.php
   ========================================================= */

DG.purchaseReturn = (function () {

    'use strict';

    var form = null;

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function calculateTotals() {
        var subtotalTotal = 0;
        var vatTotal = 0;
        var grandTotal = 0;
        var rows = form ? form.querySelectorAll('.dg-return-item-row') : [];

        rows.forEach(function (row) {
            var qtyField = row.querySelector('.return-qty');
            var priceField = row.querySelector('.unit-price');
            var vatField = row.querySelector('.vat-rate');
            var totalField = row.querySelector('.row-total');

            if (!qtyField || !priceField || !vatField || !totalField) {
                return;
            }

            var qty = toNumber(qtyField.value);
            var maxQty = toNumber(qtyField.getAttribute('data-available') || qtyField.max);

            if (qty < 0) {
                qty = 0;
                qtyField.value = 0;
            }

            if (qty > maxQty) {
                qty = maxQty;
                qtyField.value = maxQty;
            }

            var price = toNumber(priceField.value);
            var vatRate = toNumber(vatField.value);
            var subtotal = qty * price;
            var vatAmount = (subtotal * vatRate) / 100;
            var total = subtotal + vatAmount;

            totalField.value = total.toFixed(2);
            subtotalTotal += subtotal;
            vatTotal += vatAmount;
            grandTotal += total;
        });

        var subtotalEl = document.getElementById('subtotal');
        var vatEl = document.getElementById('totalVat');
        var grandEl = document.getElementById('grandTotal');

        if (subtotalEl) {
            subtotalEl.value = subtotalTotal.toFixed(2);
        }

        if (vatEl) {
            vatEl.value = vatTotal.toFixed(2);
        }

        if (grandEl) {
            grandEl.value = grandTotal.toFixed(2);
        }
    }

    function bindInputEvents() {
        document.addEventListener('input', function (event) {
            if (!form || !form.contains(event.target)) {
                return;
            }

            if (event.target.classList.contains('return-qty')) {
                calculateTotals();
            }
        });
    }

    function init() {
        form = document.getElementById('dgPurchaseReturnForm');

        if (!form) {
            return;
        }

        bindInputEvents();
        calculateTotals();
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init: init,
        calculateTotals: calculateTotals
    };

})();

/* =========================================================
   MODULE : SALES RETURN REFUND
   Screen : resources/views/company/sales-return-refund/create.blade.php
   ========================================================= */

DG.salesReturnRefund = (function () {

    'use strict';

    var form = null;
    var remaining = 0;
    var eventsBound = false;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function toMoney(value) {
        return (Math.round(toNumber(value) * 100) / 100).toFixed(2);
    }

    function setText(selector, value) {
        var element = qs(selector);

        if (element) {
            element.textContent = value;
        }
    }

    function isRecalculateTarget(target) {
        if (!target || !form) {
            return false;
        }

        return target.id === 'cash_amount'
            || (target.name === 'adjust_amount[]' && form.contains(target));
    }

    function showAlert(message, type) {
        var container = qs('.dg-container .container-fluid');

        if (!container) {
            return;
        }

        var existing = qs('.dg-alert.dg-js-alert');

        if (existing) {
            existing.parentNode.removeChild(existing);
        }

        var alertBox = document.createElement('div');
        alertBox.className = 'alert alert-' + (type || 'warning') + ' dg-alert dg-js-alert';
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = message;

        container.insertBefore(alertBox, container.firstChild);
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hasDuplicateInvoiceSelection() {
        var seen = {};
        var duplicate = false;

        qsa('input[name="adjust_amount[]"]', form).forEach(function (input) {
            if (toNumber(input.value) <= 0) {
                return;
            }

            var row = input.closest('tr');
            var invoiceField = row ? row.querySelector('input[name="sales_invoice_id[]"]') : null;

            if (!invoiceField) {
                return;
            }

            var invoiceId = invoiceField.value;

            if (seen[invoiceId]) {
                duplicate = true;
                return;
            }

            seen[invoiceId] = true;
        });

        return duplicate;
    }

    function getAdjustmentTotal() {
        var total = 0;

        qsa('input[name="adjust_amount[]"]', form).forEach(function (input) {
            var amount = toNumber(input.value);
            var due = toNumber(input.getAttribute('data-due'));

            if (amount < 0) {
                amount = 0;
                input.value = '0';
            }

            if (amount > due) {
                amount = due;
                input.value = toMoney(due);
            }

            total += amount;
        });

        return Math.round(total * 100) / 100;
    }

    function getRefundToCustomer() {
        var refundField = qs('#cash_amount', form);
        return refundField ? toNumber(refundField.value) : 0;
    }

    function clearAccountSectionFields() {
        var accountField = qs('#account_id', form);
        var referenceField = qs('#reference_no', form);
        var attachmentField = qs('#attachment', form);

        if (accountField) {
            accountField.value = '';
        }

        if (referenceField) {
            referenceField.value = '';
        }

        if (attachmentField) {
            attachmentField.value = '';
        }
    }

    function toggleAccountSection(refundToCustomer) {
        var section = qs('#refund_account_section');
        var accountField = qs('#account_id', form);
        var referenceField = qs('#reference_no', form);
        var attachmentField = qs('#attachment', form);

        if (!section) {
            return;
        }

        if (refundToCustomer > 0) {
            section.classList.remove('d-none');

            if (accountField) {
                accountField.required = true;
            }

            if (referenceField) {
                referenceField.required = false;
            }

            if (attachmentField) {
                attachmentField.required = false;
            }
        } else {
            section.classList.add('d-none');
            clearAccountSectionFields();

            if (accountField) {
                accountField.required = false;
            }

            if (referenceField) {
                referenceField.required = false;
            }

            if (attachmentField) {
                attachmentField.required = false;
            }
        }
    }

    function calculate() {
        if (!form) {
            return;
        }

        var adjustmentTotal = getAdjustmentTotal();
        var refundToCustomer = getRefundToCustomer();
        var maxRefundToCustomer = Math.max(0, Math.round((remaining - adjustmentTotal) * 100) / 100);
        var refundField = qs('#cash_amount', form);

        if (refundField && refundToCustomer > maxRefundToCustomer) {
            refundToCustomer = maxRefundToCustomer;
            refundField.value = toMoney(maxRefundToCustomer);
        }

        if (refundToCustomer < 0) {
            refundToCustomer = 0;

            if (refundField) {
                refundField.value = '0';
            }
        }

        var settlement = Math.round((adjustmentTotal + refundToCustomer) * 100) / 100;
        var remainingAfter = Math.round((remaining - settlement) * 100) / 100;

        setText('#summary_total_adjustment', toMoney(adjustmentTotal));
        setText('#summary_refund_to_customer', toMoney(refundToCustomer));
        setText('#summary_settlement', toMoney(settlement));
        setText('#summary_remaining_after', toMoney(remainingAfter));

        toggleAccountSection(refundToCustomer);
    }

    function validateForm() {
        var adjustmentTotal = getAdjustmentTotal();
        var refundToCustomer = getRefundToCustomer();
        var settlement = Math.round((adjustmentTotal + refundToCustomer) * 100) / 100;

        if (settlement <= 0) {
            showAlert('Settlement amount must be greater than zero.', 'danger');
            return false;
        }

        if (settlement > remaining) {
            showAlert('Settlement amount cannot exceed remaining refund.', 'danger');
            return false;
        }

        if (hasDuplicateInvoiceSelection()) {
            showAlert('Each invoice can only be adjusted once in a settlement.', 'danger');
            return false;
        }

        if (refundToCustomer > 0) {
            var accountField = qs('#account_id', form);

            if (!accountField || !accountField.value) {
                showAlert('Please select an account.', 'danger');
                if (accountField) {
                    accountField.focus();
                }
                return false;
            }
        }

        return true;
    }

    function handleRecalculate(event) {
        if (!isRecalculateTarget(event.target)) {
            return;
        }

        calculate();
    }

    function bindEvents() {
        if (!form || eventsBound) {
            return;
        }

        form.addEventListener('input', handleRecalculate);
        form.addEventListener('change', handleRecalculate);
        form.addEventListener('keyup', handleRecalculate);

        form.addEventListener('submit', function (event) {
            calculate();

            if (!validateForm()) {
                event.preventDefault();
            }
        });

        eventsBound = true;
    }

    function init() {
        form = document.getElementById('dgSalesReturnRefundForm');

        if (!form) {
            return;
        }

        remaining = toNumber(form.getAttribute('data-remaining'));
        bindEvents();
        calculate();
    }

    function boot() {
        init();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    return {
        init: init,
        calculate: calculate
    };

})();

/* =========================================================
   MODULE : PURCHASE RETURN REFUND
   Screen : resources/views/company/purchase-return-refunds/create.blade.php
   ========================================================= */

DG.purchaseReturnRefund = (function () {

    'use strict';

    var form = null;
    var remaining = 0;
    var eventsBound = false;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function toNumber(value) {
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    }

    function toMoney(value) {
        return (Math.round(toNumber(value) * 100) / 100).toFixed(2);
    }

    function setText(selector, value) {
        var element = qs(selector);

        if (element) {
            element.textContent = value;
        }
    }

    function isRecalculateTarget(target) {
        if (!target || !form) {
            return false;
        }

        return target.id === 'cash_amount'
            || (target.name === 'adjust_amount[]' && form.contains(target));
    }

    function showAlert(message, type) {
        var container = qs('.dg-container .container-fluid');

        if (!container) {
            return;
        }

        var existing = qs('.dg-alert.dg-js-alert');

        if (existing) {
            existing.parentNode.removeChild(existing);
        }

        var alertBox = document.createElement('div');
        alertBox.className = 'alert alert-' + (type || 'warning') + ' dg-alert dg-js-alert';
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = message;

        container.insertBefore(alertBox, container.firstChild);
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hasDuplicateInvoiceSelection() {
        var seen = {};
        var duplicate = false;

        qsa('input[name="adjust_amount[]"]', form).forEach(function (input) {
            if (toNumber(input.value) <= 0) {
                return;
            }

            var row = input.closest('tr');
            var invoiceField = row ? row.querySelector('input[name="purchase_invoice_id[]"]') : null;

            if (!invoiceField) {
                return;
            }

            var invoiceId = invoiceField.value;

            if (seen[invoiceId]) {
                duplicate = true;
                return;
            }

            seen[invoiceId] = true;
        });

        return duplicate;
    }

    function getAdjustmentTotal() {
        var total = 0;

        qsa('input[name="adjust_amount[]"]', form).forEach(function (input) {
            var amount = toNumber(input.value);
            var due = toNumber(input.getAttribute('data-due'));

            if (amount < 0) {
                amount = 0;
                input.value = '0';
            }

            if (amount > due) {
                amount = due;
                input.value = toMoney(due);
            }

            total += amount;
        });

        return Math.round(total * 100) / 100;
    }

    function getRefundToCustomer() {
        var refundField = qs('#cash_amount', form);
        return refundField ? toNumber(refundField.value) : 0;
    }

    function clearAccountSectionFields() {
        var accountField = qs('#account_id', form);
        var referenceField = qs('#reference_no', form);
        var attachmentField = qs('#attachment', form);

        if (accountField) {
            accountField.value = '';
        }

        if (referenceField) {
            referenceField.value = '';
        }

        if (attachmentField) {
            attachmentField.value = '';
        }
    }

    function toggleAccountSection(refundToCustomer) {
        var section = qs('#refund_account_section');
        var accountField = qs('#account_id', form);
        var referenceField = qs('#reference_no', form);
        var attachmentField = qs('#attachment', form);

        if (!section) {
            return;
        }

        if (refundToCustomer > 0) {
            section.classList.remove('d-none');

            if (accountField) {
                accountField.required = true;
            }

            if (referenceField) {
                referenceField.required = false;
            }

            if (attachmentField) {
                attachmentField.required = false;
            }
        } else {
            section.classList.add('d-none');
            clearAccountSectionFields();

            if (accountField) {
                accountField.required = false;
            }

            if (referenceField) {
                referenceField.required = false;
            }

            if (attachmentField) {
                attachmentField.required = false;
            }
        }
    }

    function calculate() {
        if (!form) {
            return;
        }

        var adjustmentTotal = getAdjustmentTotal();
        var refundToCustomer = getRefundToCustomer();
        var maxRefundToCustomer = Math.max(0, Math.round((remaining - adjustmentTotal) * 100) / 100);
        var refundField = qs('#cash_amount', form);

        if (refundField && refundToCustomer > maxRefundToCustomer) {
            refundToCustomer = maxRefundToCustomer;
            refundField.value = toMoney(maxRefundToCustomer);
        }

        if (refundToCustomer < 0) {
            refundToCustomer = 0;

            if (refundField) {
                refundField.value = '0';
            }
        }

        var settlement = Math.round((adjustmentTotal + refundToCustomer) * 100) / 100;
        var remainingAfter = Math.round((remaining - settlement) * 100) / 100;

        setText('#summary_total_adjustment', toMoney(adjustmentTotal));
        setText('#summary_refund_to_customer', toMoney(refundToCustomer));
        setText('#summary_settlement', toMoney(settlement));
        setText('#summary_remaining_after', toMoney(remainingAfter));

        toggleAccountSection(refundToCustomer);
    }

    function validateForm() {
        var adjustmentTotal = getAdjustmentTotal();
        var refundToCustomer = getRefundToCustomer();
        var settlement = Math.round((adjustmentTotal + refundToCustomer) * 100) / 100;

        if (settlement <= 0) {
            showAlert('Settlement amount must be greater than zero.', 'danger');
            return false;
        }

        if (settlement > remaining) {
            showAlert('Settlement amount cannot exceed remaining refund.', 'danger');
            return false;
        }

        if (hasDuplicateInvoiceSelection()) {
            showAlert('Each invoice can only be adjusted once in a settlement.', 'danger');
            return false;
        }

        if (refundToCustomer > 0) {
            var accountField = qs('#account_id', form);

            if (!accountField || !accountField.value) {
                showAlert('Please select an account.', 'danger');
                if (accountField) {
                    accountField.focus();
                }
                return false;
            }
        }

        return true;
    }

    function handleRecalculate(event) {
        if (!isRecalculateTarget(event.target)) {
            return;
        }

        calculate();
    }

    function bindEvents() {
        if (!form || eventsBound) {
            return;
        }

        form.addEventListener('input', handleRecalculate);
        form.addEventListener('change', handleRecalculate);
        form.addEventListener('keyup', handleRecalculate);

        form.addEventListener('submit', function (event) {
            calculate();

            if (!validateForm()) {
                event.preventDefault();
            }
        });

        eventsBound = true;
    }

    function init() {
        form = document.getElementById('dgPurchaseReturnRefundForm');

        if (!form) {
            return;
        }

        remaining = toNumber(form.getAttribute('data-remaining'));
        bindEvents();
        calculate();
    }

    function boot() {
        init();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    return {
        init: init,
        calculate: calculate
    };

})();

/* =========================================================
   MODULE : LAYOUT SHELL
   Screen : resources/views/company/layout.blade.php
   ========================================================= */

DG.layout = (function () {

    'use strict';

    function initAlertAutoClose() {
        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                if (alert && window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }
            }, 4000);
        });
    }

    function initPrintSupport() {
        window.addEventListener('beforeprint', function () {
            document.body.classList.add('printing');
        });

        window.addEventListener('afterprint', function () {
            document.body.classList.remove('printing');
        });
    }

    function init() {
        initAlertAutoClose();
        initPrintSupport();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        init: init
    };

})();
