<?php

namespace Tests\Feature;

use Tests\TestCase;

class SalesCreateItemComboboxTest extends TestCase
{
    public function test_sales_create_uses_one_combined_product_and_service_searchable_field(): void
    {
        $view = file_get_contents(resource_path('views/company/sales/create.blade.php'));
        $script = file_get_contents(public_path('assets/company/js/dg.js'));

        $this->assertSame(1, substr_count($view, 'dg-item-combobox-input @error'));
        $this->assertStringNotContainsString('<th scope="col">Type</th>', $view);
        $this->assertStringContainsString('class="d-none dg-sales-item-type"', $view);
        $this->assertStringContainsString('class="dg-sales-item-select d-none"', $view);
        $this->assertStringContainsString('<optgroup label="Products">', $view);
        $this->assertStringContainsString('<optgroup label="Services">', $view);
        $this->assertStringContainsString('class="dg-product-picker d-none"', $view);
        $this->assertStringContainsString('class="dg-service-picker d-none"', $view);
        $this->assertStringContainsString('name="product_id[]"', $view);
        $this->assertStringContainsString('name="service_id[]"', $view);
        $this->assertStringContainsString('DG.itemCombobox.render(row, activeSelect', $script);
        $this->assertStringContainsString("typeSelect.value = type", $script);
        $this->assertStringContainsString("productSelect.value = type === 'product' ? itemId : ''", $script);
        $this->assertStringContainsString("serviceSelect.value = type === 'service' ? itemId : ''", $script);
        $this->assertStringContainsString("event.key === 'ArrowDown' || event.key === 'ArrowUp'", $script);
        $this->assertStringContainsString("event.key === 'Escape'", $script);
        $this->assertStringContainsString('applyCombinedItemSelection(row)', $script);
    }
}
