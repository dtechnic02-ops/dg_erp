const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../public/assets/company/js/dg.js'), 'utf8');
const purchase = source.slice(source.indexOf('DG.purchaseBilling ='));
const sync = purchase.slice(purchase.indexOf('    function syncItemIdFields('), purchase.indexOf('    function syncAllItemIdFields('));
const context = vm.createContext({
    qs: (selector, row) => row[selector],
    getItemSelect: row => row.select,
});
vm.runInContext(sync, context);

for (const type of ['product', 'service']) {
    test(`selected ${type} synchronizes the submitted identity and clears the other ID`, () => {
        const row = {
            select: { value: `${type}:42`, selectedIndex: 0, options: [{ getAttribute: key => ({
                'data-item-type': type, 'data-product-id': '42', 'data-service-id': '42',
            })[key] }] },
            'input.dg-item-type': { value: '' },
            'input.dg-product-id': { value: '99' },
            'input.dg-service-id': { value: '99' },
        };
        context.syncItemIdFields(row);
        assert.equal(row['input.dg-item-type'].value, type);
        assert.equal(row['input.dg-product-id'].value, type === 'product' ? '42' : '');
        assert.equal(row['input.dg-service-id'].value, type === 'service' ? '42' : '');
    });
}
