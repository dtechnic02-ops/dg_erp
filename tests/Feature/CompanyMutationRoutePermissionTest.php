<?php

namespace Tests\Feature;

use Tests\TestCase;

class CompanyMutationRoutePermissionTest extends TestCase
{
    public function test_proven_company_mutation_gaps_have_explicit_existing_permissions(): void
    {
        $expected = [
            'company.sales.store' => 'create_sales',
            'company.sales.update' => 'edit_sales',
            'company.sales.cancel' => 'cancel_sales',
            'company.sales-payment.store' => 'create_sales_payment',
            'company.sales-payment.update' => 'edit_sales_payment',
            'company.sales-payment.cancel' => 'cancel_sales_payment',
            'company.invoice-payments.store' => 'create_sales_payment',
            'company.sales-return.store' => 'create_sales',
            'company.sales-return.update' => 'edit_sales',
            'company.sales-return.cancel' => 'cancel_sales',
            'company.sales-return-refund.store' => 'create_sales_payment',
            'company.sales-return-refund.update' => 'edit_sales_payment',
            'company.sales-return-refund.cancel' => 'cancel_sales_payment',
            'company.purchase-return.store' => 'create_purchase',
            'company.purchase-return.update' => 'edit_purchase',
            'company.purchase-return.cancel' => 'cancel_purchase',
            'company.purchase-return-refunds.store' => 'create_purchase',
            'company.purchase-return-refunds.update' => 'edit_purchase',
            'company.purchase-return-refunds.cancel' => 'cancel_purchase',
            'company.contra.store' => 'create_contra',
            'company.contra.update' => 'edit_contra',
            'company.contra.delete' => 'delete_contra',
            'company.staff-permissions.update' => 'manage_users',
            'company.staff-permissions.assign' => 'manage_users',
            'company.staff-permissions.deny' => 'manage_users',
            'company.staff-permissions.revoke' => 'manage_users',
            'company.subscription.payment.store' => 'manage_subscription_module',
        ];

        foreach ($expected as $routeName => $permission) {
            $route = app('router')->getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Missing route {$routeName}.");
            $this->assertContains('permission:' . $permission, $route->gatherMiddleware(), $routeName);
        }
    }

    public function test_subscription_gates_and_quotation_permission_resolution_are_unchanged(): void
    {
        foreach ([
            'company.delivery-notes.complete' => 'subscription.module:delivery',
            'company.crm-leads.store' => 'subscription.module:crm',
            'company.loan-account.store' => 'subscription.module:loan',
            'company.salary-sheets.store' => 'subscription.module:hr',
        ] as $routeName => $middleware) {
            $this->assertContains($middleware, app('router')->getRoutes()->getByName($routeName)->gatherMiddleware());
        }

        $this->assertContains(
            'permission:generate_quotation_invoice',
            app('router')->getRoutes()->getByName('company.quotations.convert')->gatherMiddleware()
        );
    }
}
