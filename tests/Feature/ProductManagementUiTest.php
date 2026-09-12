<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductManagementUiTest extends TestCase
{
    public function test_product_list_has_only_view_and_product_show_owns_edit_and_delete_actions(): void
    {
        $list = file_get_contents(resource_path('views/company/products/index.blade.php'));
        $show = file_get_contents(resource_path('views/company/products/show.blade.php'));

        $this->assertStringContainsString("route('company.products.show', \$p->id)", $list);
        $this->assertStringContainsString('>View</a>', $list);
        $this->assertStringContainsString('dg-action-btn dg-btn-brand">View', $list);
        $this->assertStringContainsString('<td class="dg-action-col-compact">', $list);
        $this->assertStringNotContainsString('dropdown-toggle', $list);
        $this->assertStringNotContainsString("route('company.products.edit', \$p->id)", $list);
        $this->assertStringNotContainsString("route('company.products.destroy', \$p->id)", $list);
        $this->assertStringContainsString("route('company.products.edit', \$product->id)", $show);
        $this->assertStringContainsString("route('company.products.destroy', \$product->id)", $show);
        $this->assertStringContainsString("@method('DELETE')", $show);
        $this->assertStringContainsString("confirm('Delete Product?')", $show);
        $this->assertStringContainsString('btn btn-danger dg-btn', $show);
        $this->assertStringContainsString('dg-badge-brand', $list);
    }

    public function test_product_view_displays_existing_batch_and_date_fields_with_null_fallbacks(): void
    {
        $view = file_get_contents(resource_path('views/company/products/show.blade.php'));

        $this->assertStringContainsString('Batch No :', $view);
        $this->assertStringContainsString("\$product->batch_no ?: '-'", $view);
        $this->assertStringContainsString('Manufacture Date :', $view);
        $this->assertStringContainsString("optional(\$product->manufacture_date)->format('Y-m-d') ?? '-'", $view);
        $this->assertStringContainsString('Expiry Date :', $view);
        $this->assertStringContainsString("optional(\$product->expiry_date)->format('Y-m-d') ?? '-'", $view);
        $this->assertStringContainsString('dg-badge-brand', $view);
    }
}
