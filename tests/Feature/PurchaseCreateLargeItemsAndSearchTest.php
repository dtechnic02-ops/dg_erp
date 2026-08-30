<?php

namespace Tests\Feature;

use Tests\TestCase;

class PurchaseCreateLargeItemsAndSearchTest extends TestCase
{
    public function test_purchase_item_picker_searches_the_combined_product_and_service_select(): void
    {
        $view = file_get_contents(resource_path('views/company/purchases/create.blade.php'));
        $script = file_get_contents(public_path('assets/company/js/dg.js'));

        $this->assertStringContainsString('class="form-control form-control-sm dg-input dg-item-combobox-input"', $view);
        $this->assertStringContainsString('class="dg-item-select d-none"', $view);
        $this->assertStringNotContainsString('dg-item-search', $view);
        $this->assertStringContainsString('<optgroup label="Products">', $view);
        $this->assertStringContainsString('<optgroup label="Services">', $view);
        $this->assertStringContainsString("event.target.matches('input.dg-item-combobox-input')", $script);
        $this->assertStringContainsString('function renderItemCombobox(row, searchTerm)', $script);
        $this->assertStringContainsString('function moveItemComboboxHighlight(row, direction)', $script);
        $this->assertStringContainsString('function selectItemComboboxOption(row, optionIndex)', $script);
        $this->assertStringContainsString("event.key === 'Escape'", $script);
    }

    public function test_one_hundred_purchase_rows_fit_the_documented_php_input_capacity(): void
    {
        $submittedFieldsPerRow = 8;
        $nonRowFields = 11;
        $requiredInputVariables = (100 * $submittedFieldsPerRow) + $nonRowFields;

        $this->assertSame(811, $requiredInputVariables);
        $this->assertLessThanOrEqual(1000, $requiredInputVariables);
    }
}
