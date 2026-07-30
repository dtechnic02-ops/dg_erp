@php

$company = auth()->user()?->company;
$user = auth()->user();

$activeFinancialYear = $company
    ? \App\Models\FinancialYear::where('company_id', $company->id)->where('is_active', 1)->first()
    : null;

$userRole = $user?->role?->name ?? ($user?->job_role ?: 'User');
$jobRoleVisibility = app(\App\Services\JobRoleVisibilityService::class);
$canSeeMenu = fn (string $menu): bool => $user && $jobRoleVisibility->canSeeMenu($user, $menu);
$canViewLoanAccount = $canSeeMenu('loan');
$canViewLoanPayment = $canSeeMenu('loan');
$canViewLoanSavingLedger = $canSeeMenu('loan');
$canViewEmployee = $canSeeMenu('hr');
$canViewSalary = $canSeeMenu('hr');
$canViewSalaryPayment = $canSeeMenu('hr');
$canViewDelivery = $canSeeMenu('delivery');
$canViewCrmDashboard = $canSeeMenu('crm');
$canViewCrmContact = $canSeeMenu('crm');
$canViewCrmLead = $canSeeMenu('crm');
$canViewCrmOpportunity = $canSeeMenu('crm');
$canViewCrmFollowUp = $canSeeMenu('crm');
$canViewCrmMeeting = $canSeeMenu('crm');
$canViewCrmTask = $canSeeMenu('crm');

$subscriptionService = $company ? app(\App\Services\SubscriptionService::class) : null;
$subscriptionAllowsCrm = $company && $subscriptionService?->canAccessModule($company, 'crm');
$subscriptionAllowsLoan = $company && $subscriptionService?->canAccessModule($company, 'loan');
$subscriptionAllowsHr = $company && $subscriptionService?->canAccessModule($company, 'hr');
$subscriptionAllowsDelivery = $company && $subscriptionService?->canAccessModule($company, 'delivery');

$crmOpen = request()->routeIs(
    'company.crm.*',
    'company.crm-leads.*',
    'company.crm-contacts.*',
    'company.crm-opportunities.*',
    'company.crm-follow-ups.*',
    'company.crm-meetings.*',
    'company.crm-tasks.*'
);

$salesOpen = request()->routeIs(
    'company.sales.*',
    'company.sales-payment.*',
    'company.sales-return.*',
    'company.sales-return-refund.*',
    'company.customers.*'
);

$purchaseOpen = request()->routeIs(
    'company.purchases.*',
    'company.purchase-payments.*',
    'company.purchase-return.*',
    'company.purchase-return-refunds.*',
    'company.suppliers.*'
);

$inventoryOpen = request()->routeIs(
    'company.products.*',
    'company.categories.*',
    'company.brands.*',
    'company.units.*',
    'company.services.*',
    'company.service-categories.*',
    'company.stock-ledger.*'
);

$accountsOpen = request()->routeIs(
    'company.accounts.*',
    'company.cash.accounts.*',
    'company.account-transaction.*',
    'company.income.*',
    'company.income-category.*',
    'company.expense.*',
    'company.expense-category.*',
    'company.journal.*',
    'company.contra.*',
    'company.vats.*'
);

$loanOpen = request()->routeIs(
    'company.party-account.*',
    'company.loan-account.*',
    'company.loan-payment.*',
    'company.loan-saving-ledger.*',
    'company.loan-saving-withdraw.*'
);

$hrOpen = request()->routeIs(
    'company.employee-account.*',
    'company.salary-sheets.*',
    'company.employee-payment.*',
    'company.employee-ledger.*',
    'company.payroll-register.*'
);

$reportsOpen = request()->routeIs(
    'company.vat-report.*',
    'company.supplier-statement.*',
    'company.customer-statement.*',
    'company.payroll-register.*'
);

$settingsOpen = request()->routeIs(
    'company.profile',
    'company.financial-years.*',
    'company.maintenance.*'
);

$staffManagementOpen = request()->routeIs(
    'company.users.*',
    'company.staff-permissions.*'
);

$linkActive = fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'dg-sidebar-active' : '';

$groupOpen = fn (bool $open): string => $open ? 'dg-sidebar-group-is-open' : '';

@endphp

<aside class="dg-sidebar" aria-label="ERP navigation">
    <div class="dg-sidebar-inner">
        <div class="dg-sidebar-mobile-bar">
            <span class="dg-sidebar-mobile-title">Menu</span>
            <label for="dg-mobile-nav" class="dg-sidebar-mobile-close" aria-label="Close navigation menu">
                <span class="dg-sidebar-mobile-close-icon" aria-hidden="true">×</span>
            </label>
        </div>

        <header class="dg-sidebar-header">
            <div class="dg-sidebar-brand-card">
                <img
                    class="dg-sidebar-logo"
                    src="{{ $company && $company->logo_path ? asset('companies/' . $company->id . '/' . $company->logo_path) : asset('logo.png') }}"
                    alt="DG ERP">
                <div class="dg-sidebar-company">
                    <div class="dg-sidebar-company-name">{{ $company->company_name ?? 'Company' }}</div>
                    <div class="dg-sidebar-company-meta">FY: {{ $activeFinancialYear->name ?? 'Not Active' }}</div>
                </div>
            </div>
        </header>

        <div class="dg-sidebar-scroll">
            <nav class="dg-sidebar-nav" aria-label="Main menu">
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-item">
                        <a href="{{ route('company.dashboard') }}" class="dg-sidebar-link {{ $linkActive('company.dashboard') }}">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-speedometer2"></i></span>
                            <span class="dg-sidebar-label">Dashboard</span>
                        </a>
                    </div>
                </div>

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('staff_management'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($staffManagementOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-staff-management" @if ($staffManagementOpen) checked @endif>
                        <label for="dg-nav-staff-management" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <span class="dg-sidebar-label">Staff Management</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.users.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.users.*') }}">Staff List</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('sales'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($salesOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-sales" @if ($salesOpen) checked @endif>
                        <label for="dg-nav-sales" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-cart-check"></i></span>
                            <span class="dg-sidebar-label">Sales</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.sales.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.sales.*') }}">Sales</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.sales-payment.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.sales-payment.*') }}">Payments</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.sales-return.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.sales-return.*') }}">Returns</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.sales-return-refund.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.sales-return-refund.*') }}">Refunds</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.customers.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.customers.*') }}">Customers</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if ($subscriptionAllowsCrm && $canSeeMenu('crm'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($crmOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-crm" @if ($crmOpen) checked @endif>
                        <label for="dg-nav-crm" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <span class="dg-sidebar-label">CRM</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            @if ($canViewCrmDashboard)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm.dashboard.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm.dashboard.*') }}">Dashboard</a>
                                </div>
                            @endif
                            @if ($canViewCrmLead)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-leads.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-leads.*') }}">Customer Relationships</a>
                                </div>
                            @endif
                            @if ($canViewCrmContact)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-contacts.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-contacts.*') }}">Customer Contacts</a>
                                </div>
                            @endif
                            @if ($canViewCrmOpportunity)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-opportunities.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-opportunities.*') }}">Opportunities</a>
                                </div>
                            @endif
                            @if ($canViewCrmFollowUp)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-follow-ups.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-follow-ups.*') }}">Follow-ups</a>
                                </div>
                            @endif
                            @if ($canViewCrmMeeting)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-meetings.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-meetings.*') }}">Meetings</a>
                                </div>
                            @endif
                            @if ($canViewCrmTask)
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.crm-tasks.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.crm-tasks.*') }}">Tasks</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>
                @endif

                @if($canSeeMenu('purchase'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($purchaseOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-purchase" @if ($purchaseOpen) checked @endif>
                        <label for="dg-nav-purchase" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
                            <span class="dg-sidebar-label">Purchase</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.purchases.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.purchases.*') }}">Purchases</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.purchase-payments.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.purchase-payments.*') }}">Purchase Payments</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.purchase-return.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.purchase-return.*') }}">Purchase Returns</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.purchase-return-refunds.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.purchase-return-refunds.*') }}">Purchase Refunds</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.suppliers.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.suppliers.*') }}">Suppliers</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('inventory'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($inventoryOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-inventory" @if ($inventoryOpen) checked @endif>
                        <label for="dg-nav-inventory" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                            <span class="dg-sidebar-label">Inventory</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.products.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.products.*') }}">Products</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.categories.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.categories.*') }}">Categories</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.brands.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.brands.*') }}">Brands</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.units.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.units.*') }}">Units</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.services.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.services.*') }}">Services</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.service-categories.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.service-categories.*') }}">Service Categories</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.stock-ledger.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.stock-ledger.*') }}">Stock Ledger</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('accounts') || $canSeeMenu('cash_accounts'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($accountsOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-accounts" @if ($accountsOpen) checked @endif>
                        <label for="dg-nav-accounts" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                            <span class="dg-sidebar-label">Accounts</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            @if($canSeeMenu('accounts'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.accounts.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.accounts.*') }}">Accounts</a>
                                </div>
                            @endif
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.cash.accounts.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.cash.accounts.*') }}">Cash Accounts</a>
                            </div>
                            @if($canSeeMenu('account_transactions'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.account-transaction.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.account-transaction.*') }}">Account Transactions</a>
                                </div>
                            @endif
                            @if($canSeeMenu('income'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.income.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.income.*', 'company.income-category.*') }}">Income</a>
                                </div>
                            @endif
                            @if($canSeeMenu('expense'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.expense.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.expense.*', 'company.expense-category.*') }}">Expenses</a>
                                </div>
                            @endif
                            @if($canSeeMenu('journal'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.journal.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.journal.*') }}">Journal</a>
                                </div>
                            @endif
                            @if($canSeeMenu('contra'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.contra.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.contra.*') }}">Contra</a>
                                </div>
                            @endif
                            @if($canSeeMenu('vat'))
                                <div class="dg-sidebar-child">
                                    <a href="{{ route('company.vats.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.vats.*') }}">VAT</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @endif
                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if ($subscriptionAllowsLoan && $canSeeMenu('loan'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($loanOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-loan" @if ($loanOpen) checked @endif>
                        <label for="dg-nav-loan" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-bank"></i></span>
                            <span class="dg-sidebar-label">Loan</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.party-account.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.party-account.*') }}">Party Account</a>
                            </div>
                            @if ($canViewLoanAccount)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.loan-account.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.loan-account.*') }}">Loan Ledger</a>
                            </div>
                            @endif
                            @if ($canViewLoanPayment)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.loan-payment.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.loan-payment.*') }}">Loan Payment</a>
                            </div>
                            @endif
                            @if ($canViewLoanSavingLedger)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.loan-saving-ledger.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.loan-saving-ledger.*', 'company.loan-saving-withdraw.*') }}">Loan Saving Ledger</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>
                @endif

                @if ($subscriptionAllowsHr && $canSeeMenu('hr'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($hrOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-hr" @if ($hrOpen) checked @endif>
                        <label for="dg-nav-hr" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                            <span class="dg-sidebar-label">HR</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            @if ($subscriptionAllowsHr && $canViewEmployee)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.employee-account.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.employee-account.*') }}">Employee Management</a>
                            </div>
                            @endif
                            @if ($subscriptionAllowsHr && $canViewSalary)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.salary-sheets.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.salary-sheets.*') }}">Salary Sheets</a>
                            </div>
                            @endif
                            @if ($subscriptionAllowsHr && $canViewSalaryPayment)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.employee-payment.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.employee-payment.*') }}">Salary Payments</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('reports'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($reportsOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-reports" @if ($reportsOpen) checked @endif>
                        <label for="dg-nav-reports" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-bar-chart-line"></i></span>
                            <span class="dg-sidebar-label">Reports</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.vat-report.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.vat-report.*') }}">VAT Report</a>
                            </div>
                            @if ($canViewSalary)
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.salary-sheets.print') }}" class="dg-sidebar-child-link {{ $linkActive('company.salary-sheets.print') }}">Salary Report</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.payroll-register.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.payroll-register.*') }}">Payroll Register</a>
                            </div>
                            @endif
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.supplier-statement.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.supplier-statement.*') }}">Supplier Statement</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.customer-statement.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.customer-statement.*') }}">Customer Statement</a>
                            </div>
                        </div>
                    </div>
                </div>

                @endif

                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                @if($canSeeMenu('settings'))
                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-group {{ $groupOpen($settingsOpen) }}">
                        <input type="checkbox" class="dg-sidebar-toggle" id="dg-nav-settings" @if ($settingsOpen) checked @endif>
                        <label for="dg-nav-settings" class="dg-sidebar-parent">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-gear"></i></span>
                            <span class="dg-sidebar-label">Settings</span>
                            <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                        </label>
                        <div class="dg-sidebar-submenu">
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.profile') }}" class="dg-sidebar-child-link {{ $linkActive('company.profile') }}">Profile</a>
                            </div>
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.financial-years.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.financial-years.*') }}">Financial Years</a>
                            </div>
                            @if($jobRoleVisibility->canSeeMaintenance($user))
                            <div class="dg-sidebar-child">
                                <a href="{{ route('company.maintenance.index') }}" class="dg-sidebar-child-link {{ $linkActive('company.maintenance.*') }}">Maintenance</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                @if ($subscriptionAllowsDelivery && $canSeeMenu('delivery'))
                <div class="dg-sidebar-divider" role="separator" aria-hidden="true"></div>

                <div class="dg-sidebar-section">
                    <div class="dg-sidebar-item">
                        <a href="{{ route('company.delivery-notes.index') }}" class="dg-sidebar-link {{ $linkActive('company.delivery-notes.*') }}">
                            <span class="dg-sidebar-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                            <span class="dg-sidebar-label">Delivery</span>
                        </a>
                    </div>
                </div>
                @endif
            </nav>
        </div>

        <footer class="dg-sidebar-footer">
            <div class="dg-sidebar-user-panel">
                <div class="dg-sidebar-user">{{ $user->name ?? 'User' }}</div>
                <div class="dg-sidebar-role">{{ $userRole }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dg-sidebar-button dg-sidebar-button-danger">
                    <span class="dg-sidebar-button-icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
                    <span class="dg-sidebar-button-text">Logout</span>
                </button>
            </form>
        </footer>
    </div>
</aside>
