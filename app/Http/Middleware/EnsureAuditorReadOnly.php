<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuditorReadOnly
{
    private const ALLOWED_ROUTES = [
        'company.dashboard',
        'company.customers.index', 'company.customers.show',
        'company.suppliers.index', 'company.suppliers.show',
        'company.products.index', 'company.products.show',
        'company.services.index', 'company.services.show',
        'company.categories.index', 'company.brands.index', 'company.brands.show',
        'company.units.index', 'company.service-categories.index',
        'company.sales.index', 'company.sales.show',
        'company.sales-return.index', 'company.sales-return.show',
        'company.sales-payment.index', 'company.sales-payment.show',
        'company.sales-return-refund.index', 'company.sales-return-refund.show',
        'company.purchases.index', 'company.purchases.show',
        'company.purchase-return.index', 'company.purchase-return.show',
        'company.purchase-payments.index', 'company.purchase-payments.show',
        'company.purchase-return-refunds.index', 'company.purchase-return-refunds.show',
        'company.income.index', 'company.income.show',
        'company.expense.index', 'company.expense.show',
        'company.journal.index', 'company.journal.show', 'company.journal.audit',
        'company.accounts.index', 'company.accounts.show',
        'company.cash.accounts.index',
        'company.account-transaction.index', 'company.account-transaction.show',
        'company.stock-ledger.index',
        'company.vat-report.index', 'company.vat-report.fiscal-sales',
        'company.vats.index',
        'company.accounting-reports.general-ledger',
        'company.accounting-reports.trial-balance',
        'company.accounting-reports.profit-loss',
        'company.accounting-reports.balance-sheet',
        'company.customer-statement.index', 'company.supplier-statement.index',
        'company.financial-years.index',
        'company.profile', 'company.settings.ird-cbms.edit', 'company.settings.cbms-transmissions.index', 'company.settings.cbms-transmissions.show',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ((int) $request->user()?->role_id !== Role::AUDITOR_ID) {
            return $next($request);
        }

        abort_unless(
            in_array($request->method(), ['GET', 'HEAD'], true)
                && in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true),
            403,
            'Auditor access is read-only.'
        );

        return $next($request);
    }
}
