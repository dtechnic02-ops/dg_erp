

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Services\SubscriptionService;
use App\Services\LoginRedirectService;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\CompanyApprovalController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPaymentController;
use App\Http\Controllers\Admin\SubscriptionReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Company\CustomerController;
use App\Http\Controllers\CompanyRegisterController;
use App\Http\Controllers\Company\PaymentController;
use App\Http\Controllers\Company\CompanyDashboardController as CompanyDashboard;

use App\Http\Controllers\Company\ProductController;
use App\Http\Controllers\Company\ProductCategoryController;
use App\Http\Controllers\Company\BrandController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;

use App\Http\Controllers\Company\CompanyDashboardController;

use App\Http\Controllers\Company\SupplierController;
use App\Http\Controllers\Company\AccountController;
use App\Http\Controllers\Company\CashAccountController;
use App\Http\Controllers\Company\PurchaseController;
use App\Http\Controllers\Company\VatController;
use App\Http\Controllers\Company\SalesController;
use App\Http\Controllers\Company\DeliveryNoteController;
use App\Http\Controllers\Company\CrmDashboardController;
use App\Http\Controllers\Company\CrmLeadController;
use App\Http\Controllers\Company\CrmOpportunityController;
use App\Http\Controllers\Company\CrmFollowUpController;
use App\Http\Controllers\Company\CrmMeetingController;
use App\Http\Controllers\Company\CrmTaskController;
use App\Http\Controllers\Company\CrmContactController;
use App\Http\Controllers\Company\CrmNoteController;
use App\Http\Controllers\Company\CrmAttachmentController;
use App\Http\Controllers\Company\StockLedgerController;
use App\Http\Controllers\Company\SalesReturnController;
use App\Http\Controllers\Company\ServiceController;
use App\Http\Controllers\Company\ServiceCategoryController;
use App\Http\Controllers\Company\InvoicePaymentController;
use App\Http\Controllers\Company\PurchaseReturnController;
use App\Http\Controllers\Company\PurchasePaymentController;
use App\Http\Controllers\Company\PurchaseReturnRefundController;
use App\Http\Controllers\Company\SalesPaymentController;
use App\Http\Controllers\Company\LoanAccountController;
use App\Http\Controllers\Company\LoanPaymentController;
use App\Http\Controllers\Company\SalesReturnRefundController;
use App\Http\Controllers\Company\ExpenseController;
use App\Http\Controllers\Company\ExpenseCategoryController;
use App\Http\Controllers\Company\PartyAccountController;
use App\Http\Controllers\Company\LoanSavingWithdrawController;
use App\Http\Controllers\Company\LoanSavingLedgerController;
use App\Http\Controllers\Company\EmployeeAccountController;
use App\Http\Controllers\Company\IncomeController;
use App\Http\Controllers\Company\IncomeCategoryController;
use App\Http\Controllers\Company\JournalController;
use App\Http\Controllers\Company\FinancialYearController;
use App\Http\Controllers\Company\ContraController;
use App\Http\Controllers\Company\VatReportController;
use App\Http\Controllers\Company\SalarySheetController;
use App\Http\Controllers\Company\EmployeePaymentController;
use App\Http\Controllers\Company\EmployeeLedgerController;
use App\Http\Controllers\Company\PayrollRegisterController;
use App\Http\Controllers\Company\AccountTransactionController;
use App\Http\Controllers\Company\SupplierLedgerController;
use App\Http\Controllers\Company\MaintenanceController;
use App\Http\Controllers\Company\SupplierStatementController;
use App\Http\Controllers\Company\CustomerStatementController;
use App\Http\Controllers\Company\UserPermissionController;


Route::get('/login', fn() => view('login'))->name('login');

Route::post('/login', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email','password'))) {

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->account_status !== 'active') {
            Auth::logout();

            return back()->with('error', 'Access denied');
        }

        $user->update([
            'login_at' => now(),
            'last_seen' => now(),
            'logout_at' => null,
        ]);

        return app(LoginRedirectService::class)->redirectAfterLogin($user);
    }

    return back()->with('error','Invalid Credentials');

})->name('login.post');

Route::post('/logout', function (Request $request) {
    if ($user = Auth::user()) {
        $user->update(['logout_at' => now()]);
    }

    Auth::logout();

    $request->session()->invalidate();   // 🔥 must
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');




 Route::get('/company/register', [App\Http\Controllers\CompanyRegisterController::class, 'showForm'])->name('company.register');

 Route::post('/company/register', [App\Http\Controllers\CompanyRegisterController::class, 'register'])->name('company.register.post');



//SUPER ADMIN ROUTES

Route::middleware(['auth', 'role:' . Role::SUPER_ADMIN_ID . ',' . Role::SUPER_STAFF_ID])->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:' . Role::SUPER_ADMIN_ID)
        ->name('admin.dashboard');

    Route::view('/no-access', 'admin.no_access')->name('admin.no-access');

    Route::middleware('role:' . Role::SUPER_ADMIN_ID)->prefix('platform-settings')->name('admin.platform-settings.')->group(function () {
        Route::get('/', [PlatformSettingController::class, 'index'])->name('index');
        Route::put('/general', [PlatformSettingController::class, 'updateGeneral'])->name('general.update');
        Route::put('/branding', [PlatformSettingController::class, 'updateBranding'])->name('branding.update');
        Route::put('/social-links', [PlatformSettingController::class, 'updateSocialLinks'])->name('social.update');
        Route::put('/smtp', [PlatformSettingController::class, 'updateSmtp'])->name('smtp.update');
        Route::post('/smtp/test', [PlatformSettingController::class, 'testSmtp'])->name('smtp.test');
        Route::put('/payment-gateway', [PlatformSettingController::class, 'updatePaymentGateway'])->name('gateway.update');
    });

    Route::get('/companies', [CompanyController::class, 'index'])->name('admin.companies');
    Route::get('/company/{company}', [CompanyController::class, 'show'])->name('admin.company.show');

    Route::post('/company/block/{id}', [CompanyController::class, 'block'])->name('admin.company.block');
    Route::post('/company/unblock/{id}', [CompanyController::class, 'unblock'])->name('admin.company.unblock');
    Route::post('/company/delete/{id}', [CompanyController::class, 'delete'])->middleware('permission:delete_company')->name('admin.company.delete');

    Route::post('/company/limit/{id}', [CompanyController::class, 'updateLimit'])->middleware('permission:edit_company')->name('admin.company.limit');
    Route::post('/company/customer-limit/{id}', [CompanyController::class, 'updateCustomerLimit'])->middleware('permission:edit_company')->name('admin.company.customer.limit');
    Route::post('/company/reset/{company}', [CompanyController::class, 'requestPasswordReset'])->middleware('permission:reset_company_password')->name('admin.company.reset');
    Route::get('/company/{company}/reset/verify', [CompanyController::class, 'showPasswordResetVerification'])->middleware('permission:reset_company_password')->name('admin.company.reset.verify.form');
    Route::post('/company/{company}/reset/verify', [CompanyController::class, 'verifyPasswordResetOtp'])->middleware('permission:reset_company_password')->name('admin.company.reset.verify');
    Route::get('/company/{company}/reset/password', [CompanyController::class, 'showPasswordResetForm'])->middleware('permission:reset_company_password')->name('admin.company.reset.password.form');
    Route::post('/company/{company}/reset/password', [CompanyController::class, 'completePasswordReset'])->middleware('permission:reset_company_password')->name('admin.company.reset.password');


    // Subscription Module
    Route::prefix('subscription-plans')->name('admin.subscription-plans.')->controller(SubscriptionPlanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/activate/{id}', 'activate')->name('activate');
        Route::post('/deactivate/{id}', 'deactivate')->name('deactivate');
    });

    Route::prefix('subscriptions')->name('admin.subscriptions.')->controller(SubscriptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/free-trial/{companyId}', 'assignFreeTrial')->name('free-trial');
        Route::post('/renew/{companyId}', 'renew')->name('renew');
        Route::post('/upgrade/{companyId}', 'upgrade')->name('upgrade');
        Route::post('/downgrade/{companyId}', 'downgrade')->name('downgrade');
        Route::post('/expire/{companyId}', 'expire')->name('expire');
        Route::post('/cancel/{subscriptionId}', 'cancel')->name('cancel');
    });

    Route::prefix('subscription-payments')->name('admin.subscription-payments.')->controller(SubscriptionPaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/manual', 'manualForm')->name('manual');
        Route::post('/manual', 'manualStore')->name('manual.store');
        Route::post('/verify/{id}', 'verify')->name('verify');
        Route::post('/approve/{id}', 'approve')->name('approve');
        Route::post('/reject/{id}', 'reject')->name('reject');
        Route::get('/invoice/{id}', 'invoice')->name('invoice');
    });

    Route::get('/subscription-reports', [SubscriptionReportController::class, 'index'])->name('admin.subscription-reports.index');

    // Legacy route aliases
    Route::get('/plans', fn () => redirect()->route('admin.subscription-plans.index'))->name('admin.plans');
    Route::get('/payments', fn () => redirect()->route('admin.subscription-payments.index'))->name('admin.payments');
    Route::get('/manual-payment', fn () => redirect()->route('admin.subscription-payments.manual'))->name('admin.manual.payment');
    Route::post('/manual-payment', [SubscriptionPaymentController::class, 'manualStore'])->name('admin.manual.payment.store');
    Route::post('/payment/approve/{id}', [SubscriptionPaymentController::class, 'approve'])->name('admin.payment.approve');
    Route::post('/payment/reject/{id}', [SubscriptionPaymentController::class, 'reject'])->name('admin.payment.reject');
    Route::get('/invoice/{id}', [SubscriptionPaymentController::class, 'invoice'])->name('admin.invoice');

     //company Rgistetion
         Route::get('/registrations', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'index'])->name('admin.registrations');
    Route::get('/registration/{registration}', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'show'])->name('admin.registration.show');

    Route::post('/approve/{id}', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'approve'])->name('admin.approve');
    Route::post('/reject/{id}', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'reject'])->name('admin.reject');
    
        //Payments legacy removed — use subscription-payments routes

    Route::middleware('role:' . Role::SUPER_ADMIN_ID)
        ->prefix('super-staff')
        ->name('admin.super-staff.')
        ->controller(\App\Http\Controllers\Admin\SuperStaffController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}', 'show')->name('show');
            Route::get('/{user}/edit', 'edit')->name('edit');
            Route::put('/{user}', 'update')->name('update');
            Route::post('/{user}/block', 'block')->name('block');
            Route::post('/{user}/unblock', 'unblock')->name('unblock');
            Route::get('/{user}/permissions', 'editPermissions')->name('permissions.edit');
            Route::put('/{user}/permissions', 'updatePermissions')->name('permissions.update');
        });

       //Admin Users
        Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users');
        Route::get('/user/{user}', [AdminUserController::class, 'show'])->name('admin.user.show');

      Route::post('/user/{id}/block', [AdminUserController::class, 'block'])->name('admin.user.block');
     Route::post('/user/{id}/unblock', [AdminUserController::class, 'unblock'])->name('admin.user.unblock');
      Route::post('/user/delete/{id}', [UserController::class, 'delete'])->name('admin.user.delete');
      Route::post('/user/{user}/reset', [AdminUserController::class, 'requestPasswordReset'])->name('admin.user.reset');
      Route::get('/user/{user}/reset/verify', [AdminUserController::class, 'showPasswordResetVerification'])->name('admin.user.reset.verify.form');
      Route::post('/user/{user}/reset/verify', [AdminUserController::class, 'verifyPasswordResetOtp'])->name('admin.user.reset.verify');
      Route::get('/user/{user}/reset/password', [AdminUserController::class, 'showPasswordResetForm'])->name('admin.user.reset.password.form');
      Route::post('/user/{user}/reset/password', [AdminUserController::class, 'completePasswordReset'])->name('admin.user.reset.password');
});


Route::middleware(['auth','company.user',\App\Http\Middleware\UpdateLastSeen::class,'subscription'])->prefix('company')->name('company.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile',[\App\Http\Controllers\Company\CompanyClientController::class, 'profile'])->middleware('permission:view_company_profile')->name('profile');

    Route::post('/profile/update',[\App\Http\Controllers\Company\CompanyClientController::class, 'update'])->middleware('permission:edit_company_profile')->name('profile.update');

    Route::get('/subscription', [\App\Http\Controllers\Company\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/payment', [\App\Http\Controllers\Company\PaymentController::class, 'store'])->name('subscription.payment.store');
    
  
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

     Route::get('/dashboard',[\App\Http\Controllers\Company\DashboardController::class,'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    Route::prefix('users')->name('users.')->group(function () {

        Route::get('/', [UserController::class, 'index'])
            ->middleware('permission:view_users')
            ->name('index');

        Route::post('/store', [UserController::class, 'store'])
            ->middleware('permission:manage_users')
            ->name('store');

        Route::get('/edit/{id}', [UserController::class, 'edit'])
            ->middleware('permission:edit_users')
            ->name('edit');

        Route::post('/update/{id}', [UserController::class, 'update'])
            ->middleware('permission:edit_users')
            ->name('update');

        Route::post('/block/{id}', [UserController::class, 'block'])
            ->middleware('permission:block_user')
            ->name('block');

        Route::post('/unblock/{id}', [UserController::class, 'unblock'])
            ->middleware('permission:block_user')
            ->name('unblock');

        Route::post('/delete/{id}', [UserController::class, 'destroy'])
            ->middleware('permission:delete_user')
            ->name('delete');

        Route::post('/reset/{id}', [UserController::class, 'resetPassword'])
            ->middleware('permission:reset_password')
            ->name('reset');

    });
    Route::prefix('staff-permissions')
    ->name('staff-permissions.')
    ->group(function () {

        Route::get('/{user}', [UserPermissionController::class, 'edit'])
            ->name('edit');

        Route::put('/{user}', [UserPermissionController::class, 'update'])
            ->name('update');

        Route::post('/{user}/assign/{permission}', [UserPermissionController::class, 'assign'])
            ->name('assign');

        Route::post('/{user}/deny/{permission}', [UserPermissionController::class, 'deny'])
            ->name('deny');

        Route::delete('/{user}/revoke/{permission}', [UserPermissionController::class, 'revoke'])
            ->name('revoke');

    });

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/permissions', [UserController::class, 'permissionPage'])
        ->middleware('permission:manage_users')
        ->name('permissions.index');

    Route::post('/permissions', [UserController::class, 'updateRolePermission'])
        ->middleware('permission:manage_users')
        ->name('permissions.update');

    /*
    |--------------------------------------------------------------------------
    | Maintenance
    |--------------------------------------------------------------------------
    
    */
    Route::prefix('maintenance')
    ->name('maintenance.')
    ->group(function () {

        Route::get(
            '/',
            [MaintenanceController::class, 'index']
        )->name('index');

        Route::post(
            '/recalculate-ledger',
            [MaintenanceController::class, 'recalculateLedger']
        )->name('recalculate.ledger');

        Route::post(
            '/recalculate-stock',
            [MaintenanceController::class, 'recalculateStock']
        )->name('recalculate.stock');

        Route::post(
            '/recalculate-purchase-invoices',
            [MaintenanceController::class, 'recalculatePurchaseInvoices']
        )->name('recalculate.purchase.invoices');

        Route::post(
            '/recalculate-customer-statement',
            [MaintenanceController::class, 'recalculateCustomerStatement']
        )->name('recalculate.customer.statement');

        Route::post(
            '/recalculate-supplier-statement',
            [MaintenanceController::class, 'recalculateSupplierStatement']
        )->name('recalculate.supplier.statement');

    });





    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */

    Route::get('products/print', [ProductController::class, 'print'])->name('products.print');

    Route::get('products/{id}/print', [ProductController::class, 'printProfile'])->name('products.printProfile');

    Route::resource('products',ProductController::class);

    /*
    |--------------------------------------------------------------------------
    | BRANDS
    |--------------------------------------------------------------------------
    */

    Route::prefix('brands')
        ->name('brands.')
        ->group(function () {

        Route::get('/',
            [BrandController::class, 'index']
        )->name('index');

        Route::post('/',
            [BrandController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [BrandController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [BrandController::class, 'destroy']
        )->name('delete');

        Route::get('/print',[BrandController::class,'print'])->name('print');

        Route::get('/{id}/print',[BrandController::class, 'printProfile'])->name('printProfile');

        Route::get('/{id}',[BrandController::class, 'show'])->name('show');

    });

    /*
    |--------------------------------------------------------------------------
    | PRODUCT CATEGORIES
    |--------------------------------------------------------------------------
    */

    Route::prefix('categories')->name('categories.')->group(function () {
       Route::get('/',[ProductCategoryController::class, 'index'])->name('index');
       Route::post('/',[ProductCategoryController::class, 'store'])->name('store');
       Route::post('/update/{id}',[ProductCategoryController::class, 'update'])->name('update');
       Route::post('/delete/{id}',[ProductCategoryController::class, 'destroy'])->name('delete');
       Route::get('/print',[ProductCategoryController::class, 'print'])->name('print');

    });

    /*
    |--------------------------------------------------------------------------
    | SUPPLIERS
    |--------------------------------------------------------------------------
    */

    Route::prefix('suppliers')
        ->name('suppliers.')
        ->group(function () {

        Route::get('/',
            [SupplierController::class, 'index']
        )->name('index');

        Route::post('/',
            [SupplierController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [SupplierController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [SupplierController::class, 'destroy']
        )->name('delete');

        Route::get('/print',
            [SupplierController::class, 'print']
        )->name('print');

        Route::get('/show/{id}/print',
            [SupplierController::class, 'printProfile']
        )->name('printProfile');

         Route::get(
            '/show/{id}',
            [SupplierController::class,'show']
        )->name('show');
       
        

    });
    

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */

    Route::prefix('customers')
        ->name('customers.')
        ->group(function () {

        Route::get('/',
            [CustomerController::class, 'index']
        )->name('index');

        Route::post('/',
            [CustomerController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [CustomerController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [CustomerController::class, 'destroy']
        )->name('delete');
         
        Route::get('/print',[CustomerController::class,'print'])->name('print');

        Route::get('/{id}/print',[CustomerController::class, 'printProfile'])->name('printProfile');

        Route::get('/{id}',[CustomerController::class, 'show'])->name('show');

    });

      /*
    |--------------------------------------------------------------------------
    | FinancialYear
    |--------------------------------------------------------------------------
    */
       Route::resource(
'financial-years',
FinancialYearController::class
);




    /*
    |--------------------------------------------------------------------------
    | UNITS
    |--------------------------------------------------------------------------
    */
Route::prefix('units')
->name('units.')
->group(function () {

Route::get(
'/',
[\App\Http\Controllers\Company\UnitController::class,'index']
)->name('index');


Route::post(
'/store',
[\App\Http\Controllers\Company\UnitController::class,'store']
)->name('store');


Route::post(
'/update/{id}',
[\App\Http\Controllers\Company\UnitController::class,'update']
)->name('update');


Route::post(
'/delete/{id}',
[\App\Http\Controllers\Company\UnitController::class,'destroy']
)->name('destroy');


Route::get(
'/print',
[\App\Http\Controllers\Company\UnitController::class,'print']
)->name('print');

});



    /*
    |--------------------------------------------------------------------------
    | PRODUCT IMPORT EXPORT
    |--------------------------------------------------------------------------
    */

    Route::get(
        'products/export/excel',
        [ProductController::class, 'exportExcel']
    )->name('products.export.excel');

    Route::get(
        'products/export/pdf',
        [ProductController::class, 'exportPdf']
    )->name('products.export.pdf');

    Route::post(
        'products/import',
        [ProductController::class, 'importExcel']
    )->name('products.import');

    /*
    |--------------------------------------------------------------------------
    | ACCOUNTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('accounts')
        ->name('accounts.')
        ->group(function () {

        Route::get('/',
            [AccountController::class, 'index']
        )->name('index');

        Route::post('/',
            [AccountController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [AccountController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [AccountController::class, 'destroy']
        )->name('delete');

        Route::get('/show/{id}',
            [AccountController::class,'show']
        )->name('show');

        Route::get('/show/{id}/print',
            [AccountController::class, 'printProfile']
        )->name('printProfile');

        Route::get( '/print', [AccountController::class,'print'] ) ->name( 'print' );

    });

    /*
    |--------------------------------------------------------------------------
    | CASH ACCOUNTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('cash-accounts')
        ->name('cash.accounts.')
        ->group(function () {

        Route::get('/',
            [CashAccountController::class, 'index']
        )->name('index');

        Route::post('/',
            [CashAccountController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [CashAccountController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [CashAccountController::class, 'destroy']
        )->name('delete');

    });

    /*
    |--------------------------------------------------------------------------
    | VATS
    |--------------------------------------------------------------------------
    */

    Route::prefix('vats')
        ->name('vats.')
        ->group(function () {

        Route::get('/',
            [VatController::class, 'index']
        )->name('index');

        Route::post('/store',
            [VatController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [VatController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [VatController::class, 'destroy']
        )->name('delete');

    });


/*
|--------------------------------------------------------------------------
| VAT REPORT
|--------------------------------------------------------------------------
*/

Route::prefix('vat-reports')
    ->name('vat-report.')
    ->group(function () {

    Route::get(
        '/',
        [VatReportController::class, 'index']
    )->name('index');

    Route::get(
        '/print',
        [VatReportController::class, 'print']
    )->name('print');

});



    /*
    |--------------------------------------------------------------------------
    | PURCHASES
    |--------------------------------------------------------------------------
    */

    Route::prefix('purchases')
        ->name('purchases.')
        ->group(function () {

        Route::get('/',
            [PurchaseController::class, 'index']
        )->name('index');

        Route::get('/create',
            [PurchaseController::class, 'create']
        )->name('create');

        Route::post('/store',
            [PurchaseController::class, 'store']
        )->name('store');

       


        Route::get('/show/{id}',
            [PurchaseController::class, 'show']
        )->name('show');

        Route::get(
            '/edit/{id}',
            [PurchaseController::class, 'edit']
        )->name('edit');

        Route::put(
            '/update/{id}',
            [PurchaseController::class, 'update']
        )->name('update');

        Route::get(
            '/print-list',
            [PurchaseController::class, 'printList']
        )->name('print-list');

        Route::get(
            '/print/{id}',
            [PurchaseController::class, 'print']
        )->name('print');

        Route::post(
            '/cancel/{id}',
            [PurchaseController::class, 'cancel']
        )->name('cancel');

    });


    /*
    |--------------------------------------------------------------------------
    | SALES
    |--------------------------------------------------------------------------
    */
    Route::prefix('sales')
    ->name('sales.')
    ->group(function () {

        Route::get(
            '/',
            [SalesController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [SalesController::class, 'create']
        )->name('create');

        Route::post(
            '/store',
            [SalesController::class, 'store']
        )->name('store');

        Route::get(
            '/show/{id}',
            [SalesController::class, 'show']
        )->name('show');

        Route::get(
            '/edit/{id}',
            [SalesController::class, 'edit']
        )->name('edit');

        Route::put(
            '/update/{id}',
            [SalesController::class, 'update']
        )->name('update');

        /**
         * PRINT ROUTE
         */

        Route::get(
            '/print-list',
            [SalesController::class, 'printList']
        )->name('print-list');

        Route::get(
            '/print/{id}',
            [SalesController::class, 'print']
        )->name('print');

        Route::post(
            '/cancel/{id}',
            [SalesController::class, 'cancel']
        )->name('cancel');

    });

    Route::middleware('subscription.module:delivery')->group(function () {
    Route::prefix('delivery-notes')
        ->name('delivery-notes.')
        ->controller(DeliveryNoteController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id}', 'show')->name('show');
            Route::get('/process/{id}', 'process')->name('process');
            Route::post('/complete/{id}', 'complete')->name('complete');
            Route::get('/print/{id}', 'print')->name('print');
            Route::post('/cancel/{id}', 'cancel')->name('cancel');
        });
    });

    Route::middleware('subscription.module:crm')->group(function () {
    Route::prefix('crm')->name('crm.')->controller(CrmDashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard.index');
    });

    Route::prefix('crm-leads')->name('crm-leads.')->controller(CrmLeadController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/close/{id}', 'close')->name('close');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-opportunities')->name('crm-opportunities.')->controller(CrmOpportunityController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/close/{id}', 'close')->name('close');
        Route::post('/won/{id}', 'won')->name('won');
        Route::post('/lost/{id}', 'lost')->name('lost');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-contacts')->name('crm-contacts.')->controller(CrmContactController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-follow-ups')->name('crm-follow-ups.')->controller(CrmFollowUpController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-meetings')->name('crm-meetings.')->controller(CrmMeetingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/complete/{id}', 'complete')->name('complete');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-tasks')->name('crm-tasks.')->controller(CrmTaskController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/complete/{id}', 'complete')->name('complete');
        Route::post('/archive/{id}', 'archive')->name('archive');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });

    Route::prefix('crm-notes')->name('crm-notes.')->controller(CrmNoteController::class)->group(function () {
        Route::post('/store', 'store')->name('store');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/archive/{id}', 'archive')->name('archive');
    });

    Route::prefix('crm-attachments')->name('crm-attachments.')->controller(CrmAttachmentController::class)->group(function () {
        Route::post('/store', 'store')->name('store');
        Route::get('/download/{id}', 'download')->name('download');
        Route::get('/preview/{id}', 'preview')->name('preview');
        Route::post('/archive/{id}', 'archive')->name('archive');
    });
    });

    /*
    |--------------------------------------------------------------------------
    | STOCK LEDGER
    |--------------------------------------------------------------------------
    */

    Route::prefix('stock-ledger')
        ->name('stock-ledger.')
        ->group(function () {

        Route::get('/',
            [StockLedgerController::class, 'index']
        )->name('index');

        Route::post('/sync',
            [StockLedgerController::class, 'sync']
        )->name('sync');

        Route::get('/pdf',
            [StockLedgerController::class, 'pdf']
        )->name('pdf');

    });

 

   /*
|--------------------------------------------------------------------------
| SALES RETURN
|--------------------------------------------------------------------------
*/



    /**
     * INDEX
     */
    Route::prefix('sales-returns')
    ->name('sales-return.')
    ->group(function () {

     Route::get(
        '/',
        [SalesReturnController::class, 'index']
    )->name('index');

    Route::get(
        '/create/{id}',
        [SalesReturnController::class, 'create']
    )->name('create');

    Route::post(
        '/store',
        [SalesReturnController::class, 'store']
    )->name('store');

    Route::get(
        '/show/{id}',
        [SalesReturnController::class, 'show']
    )->name('show');

    Route::get(
        '/edit/{id}',
        [SalesReturnController::class, 'edit']
    )->name('edit');

    Route::post(
        '/update/{id}',
        [SalesReturnController::class, 'update']
    )->name('update');

    Route::get(
        '/print/{id}',
        [SalesReturnController::class, 'print']
    )->name('print');

    Route::post(
        '/cancel/{id}',
        [SalesReturnController::class, 'cancel']
    )->name('cancel');

    


});




    /*
    |--------------------------------------------------------------------------
    | SERVICE CATEGORIES
    |--------------------------------------------------------------------------
    */

    Route::prefix('service-categories')
        ->name('service-categories.')
        ->group(function () {

        Route::get('/',
         [ServiceCategoryController::class, 'index']
         )->name('index');

        Route::post('/store',
            [ServiceCategoryController::class, 'store']
        )->name('store');

        Route::post('/update/{id}',
            [ServiceCategoryController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [ServiceCategoryController::class, 'destroy']
        )->name('delete');

        Route::get('/print',
            [ServiceCategoryController::class, 'print']
        )->name('print');

    });



    /*
    |--------------------------------------------------------------------------
    | SERVICES
    |--------------------------------------------------------------------------
    */

    Route::prefix('services')
        ->name('services.')
        ->group(function () {

        Route::get('/',
            [ServiceController::class, 'index']
        )->name('index');

        Route::get('/print',
            [ServiceController::class, 'print']
        )->name('print');

        Route::get('/create',
            [ServiceController::class, 'create']
        )->name('create');

        Route::post('/store',
            [ServiceController::class, 'store']
        )->name('store');

        Route::get('/{id}/print',
            [ServiceController::class, 'printProfile']
        )->name('printProfile');

        Route::get('/{id}/edit',
            [ServiceController::class, 'edit']
        )->name('edit');

        Route::post('/update/{id}',
            [ServiceController::class, 'update']
        )->name('update');

        Route::post('/delete/{id}',
            [ServiceController::class, 'destroy']
        )->name('delete');

        Route::get('/{id}',
            [ServiceController::class, 'show']
        )->name('show');

    });

    /*
    |--------------------------------------------------------------------------
    | INVOICE PAYMENTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('invoice-payments')
        ->name('invoice-payments.')
        ->group(function () {

        Route::post('/store',
            [InvoicePaymentController::class, 'store']
        )->name('store');

    });
    /*
|--------------------------------------------------------------------------
| PURCHASE PAYMENTS
|--------------------------------------------------------------------------
*/
Route::prefix('purchase-payments')
    ->name('purchase-payments.')
    ->group(function () {

        Route::get('/',[PurchasePaymentController::class, 'index'])->name('index');
        Route::get('/create/{id}',[PurchasePaymentController::class, 'create'])->name('create');
        Route::post('/store', [PurchasePaymentController::class, 'store'])->name('store');
        Route::get('/edit/{id}',[PurchasePaymentController::class, 'edit'])->name('edit');
        Route::post('/update/{id}',[PurchasePaymentController::class, 'update'])->name('update');
        Route::post('/cancel/{id}',[PurchasePaymentController::class, 'cancel'])->name('cancel');

        Route::get(
            '/print-list',
            [PurchasePaymentController::class, 'printList']
        )->name('print-list');

        Route::get(
            '/print/{id}',
            [PurchasePaymentController::class, 'print']
        )->name('print');

        Route::get('/show/{id}',[PurchasePaymentController::class,'show'])->name('show');

    });



   /*
|--------------------------------------------------------------------------
| PURCHASE RETURNS
|--------------------------------------------------------------------------
*/

      Route::prefix('purchase-returns')
         ->name('purchase-return.')
                  ->group(function () {

              Route::get('/',
        [PurchaseReturnController::class, 'index']
                )->name('index');

                   Route::get('/create/{id}',
        [PurchaseReturnController::class, 'create']
                 )->name('create');

                Route::post('/store',
        [PurchaseReturnController::class, 'store']
              )->name('store');

               Route::get('/show/{id}',
        [PurchaseReturnController::class, 'show']
        )->name('show');

               Route::get('/edit/{id}',
        [PurchaseReturnController::class, 'edit']
        )->name('edit');

               Route::post('/update/{id}',
        [PurchaseReturnController::class, 'update']
        )->name('update');

        Route::get(
            '/print-list',
            [PurchaseReturnController::class, 'printList']
        )->name('print-list');

        Route::get(
            '/print/{id}',
            [PurchaseReturnController::class, 'print']
        )->name('print');

Route::post(
    '/cancel/{id}',
    [PurchaseReturnController::class, 'cancel']
)->name('cancel');


    });
/*
|--------------------------------------------------------------------------
| PURCHASE RETURN REFUNDS
|--------------------------------------------------------------------------
*/

   Route::prefix('purchase-return-refunds')
    ->name('purchase-return-refunds.')
    ->group(function () {

    Route::get('/',
        [PurchaseReturnRefundController::class, 'index']
    )->name('index');

    Route::get('/create/{id}',
        [PurchaseReturnRefundController::class, 'create']
    )->name('create');

    Route::post('/store',
        [PurchaseReturnRefundController::class, 'store']
    )->name('store');

    Route::get('/show/{id}',
        [PurchaseReturnRefundController::class, 'show']
    )->name('show');

    Route::get('/edit/{id}',
        [PurchaseReturnRefundController::class, 'edit']
    )->name('edit');

    Route::post('/update/{id}',
        [PurchaseReturnRefundController::class, 'update']
    )->name('update');

        Route::get(
            '/print-list',
            [PurchaseReturnRefundController::class, 'printList']
        )->name('print-list');

        Route::get(
            '/print/{id}',
            [PurchaseReturnRefundController::class, 'print']
        )->name('print');

Route::post(
    '/cancel/{id}',
    [PurchaseReturnRefundController::class,'cancel']
)->name('cancel');

});
/*
|--------------------------------------------------------------------------
| Supplier Ledger
|--------------------------------------------------------------------------
*/

Route::prefix('supplier-ledger')->name('supplier-ledger.')->group(function () {

    Route::get(
        '/{id}',
        [SupplierLedgerController::class, 'index']
    )->name('index');

});

/*
|--------------------------------------------------------------------------
| supplier-statement
|--------------------------------------------------------------------------
*/


Route::prefix('supplier-statement')
    ->name('supplier-statement.')
    ->group(function () {

    Route::get(
        '/',
        [SupplierStatementController::class, 'index']
    )->name('index');

});


/*
|--------------------------------------------------------------------------
| customer-statement
|--------------------------------------------------------------------------
*/


Route::prefix('customer-statement')
    ->name('customer-statement.')
    ->group(function () {

    Route::get(
        '/',
        [CustomerStatementController::class, 'index']
    )->name('index');

});
/*
|--------------------------------------------------------------------------
| sales-return
|--------------------------------------------------------------------------
*/

Route::prefix('sales-return-refunds')
    ->name('sales-return-refund.')
    ->group(function () {

 Route::get(
        '/',
        [SalesReturnRefundController::class, 'index']
    )->name('index');


    Route::get(
        '/create/{id}',
        [SalesReturnRefundController::class, 'create']
    )->name('create');

    Route::post(
        '/store',
        [SalesReturnRefundController::class, 'store']
    )->name('store');

    Route::get(
        '/show/{id}',
        [SalesReturnRefundController::class, 'show']
    )->name('show');

    Route::get(
        '/edit/{id}',
        [SalesReturnRefundController::class, 'edit']
    )->name('edit');

    Route::post(
        '/update/{id}',
        [SalesReturnRefundController::class, 'update']
    )->name('update');

    Route::get(
        '/print/{id}',
        [SalesReturnRefundController::class, 'print']
    )->name('print');

    Route::post(
        '/cancel/{id}',
        [SalesReturnRefundController::class, 'cancel']
    )->name('cancel');

});

Route::prefix('sales-payments')
    ->name('sales-payment.')
    ->group(function () {

    Route::get(
        '/',
        [SalesPaymentController::class, 'index']
    )->name('index');

    Route::get(
        '/create/{id}',
        [SalesPaymentController::class, 'create']
    )->name('create');

    Route::post(
        '/store',
        [SalesPaymentController::class, 'store']
    )->name('store');

    Route::get(
        '/show/{id}',
        [SalesPaymentController::class, 'show']
    )->name('show');

    Route::get(
        '/edit/{id}',
        [SalesPaymentController::class, 'edit']
    )->name('edit');

    Route::post(
        '/update/{id}',
        [SalesPaymentController::class, 'update']
    )->name('update');

    Route::get(
        '/print-list',
        [SalesPaymentController::class, 'printList']
    )->name('print-list');

    Route::get(
        '/print/{id}',
        [SalesPaymentController::class, 'print']
    )->name('print');

    Route::post(
        '/cancel/{id}',
        [SalesPaymentController::class, 'cancel']
    )->name('cancel');

});

/*
|--------------------------------------------------------------------------
| EXPENSE CATEGORY
|--------------------------------------------------------------------------
*/

Route::prefix('expense-categories')->name('expense-category.')->controller(ExpenseCategoryController::class)
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::post('/delete/{id}', 'destroy')->name('delete');
});

/*
|--------------------------------------------------------------------------
| EXPENSES
|--------------------------------------------------------------------------
*/

Route::prefix('expenses')->name('expense.')->controller(ExpenseController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::post('/cancel/{id}', 'cancel')->name('cancel');
    Route::get('/print', 'print')->name('print');
    Route::get('/print/{id}', 'printVoucher')->name('print-voucher');
});

/*
|--------------------------------------------------------------------------
| LOAN ACCOUNTS
|--------------------------------------------------------------------------
*/

Route::middleware('subscription.module:loan')->group(function () {
Route::prefix('loan-accounts')->name('loan-account.')->group(function(){
      Route::get('/',[LoanAccountController::class,'index'])->name('index');
      Route::get('/create',[LoanAccountController::class,'create'])->name('create');
      Route::post('/store',[LoanAccountController::class,'store'])->name('store');
      Route::get('/show/{id}',[LoanAccountController::class,'show'])->name('show');
      Route::get('/edit/{id}',[LoanAccountController::class,'edit'])->name('edit');
      Route::post('/update/{id}',[LoanAccountController::class,'update'])->name('update');
      Route::post('/cancel/{id}',[LoanAccountController::class,'cancel'])->name('cancel');
});

/*
|--------------------------------------------------------------------------
| LOAN PAYMENTS
|--------------------------------------------------------------------------
*/

Route::prefix('loan-payments')->name('loan-payment.')->group(function(){
    Route::get('/',[LoanPaymentController::class,'index'])->name('index');
    Route::get('/create/{id}',[LoanPaymentController::class,'create'])->name('create');
    Route::post('/store',[LoanPaymentController::class,'store'])->name('store');
    Route::get('/show/{id}',[LoanPaymentController::class,'show'])->name('show');
    Route::get('/edit/{id}',[LoanPaymentController::class,'edit'])->name('edit');
    Route::post('/update/{id}',[LoanPaymentController::class,'update'])->name('update');
    Route::post('/cancel/{id}',[LoanPaymentController::class,'cancel'])->name('cancel');
    Route::get('/print/{id}',[LoanPaymentController::class,'print'])->name('print');
});

/*
|--------------------------------------------------------------------------
| PARTY ACCOUNTS
|--------------------------------------------------------------------------
*/
    Route::prefix('party-accounts')->name('party-account.')->group(function () {
        Route::get('/', [PartyAccountController::class, 'index'])->name('index');
        Route::get('/create', [PartyAccountController::class, 'create'])->name('create');
        Route::post('/store', [PartyAccountController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PartyAccountController::class, 'update'])->name('update');
        Route::post('/delete/{id}', [PartyAccountController::class, 'destroy'])->name('delete');
        Route::get('/show/{id}', [PartyAccountController::class, 'show'])->name('show');
    });
/*
|--------------------------------------------------------------------------
| LOAN SAVING LEDGER
|--------------------------------------------------------------------------
*/

    Route::prefix('loan-saving-ledgers')->name('loan-saving-ledger.')->group(function () {
        Route::get('/', [LoanSavingLedgerController::class, 'index'])->name('index');
        Route::get('/show/{id}', [LoanSavingLedgerController::class, 'show'])->name('show');
    });

/*
|--------------------------------------------------------------------------
| LOAN SAVING WITHDRAW
|--------------------------------------------------------------------------
*/

    Route::prefix('loan-saving-withdraws')->name('loan-saving-withdraw.')->group(function(){
          Route::get('/create/{id}',[LoanSavingWithdrawController::class,'create'])->name('create');
          Route::post('/store',[LoanSavingWithdrawController::class,'store'])->name('store');
    });
});

/*
|--------------------------------------------------------------------------
| Empulay Account
|--------------------------------------------------------------------------
*/
    Route::middleware('subscription.module:hr')->group(function () {
    Route::prefix('employee-accounts')->name('employee-account.')->group(function(){
        Route::get('/',[EmployeeAccountController::class,'index'])->name('index');
        Route::get('/print',[EmployeeAccountController::class,'print'])->name('print');
        Route::get('/create',[EmployeeAccountController::class,'create'])->name('create');
        Route::post('/store',[EmployeeAccountController::class,'store'])->name('store');
        Route::get('/show/{id}',[EmployeeAccountController::class,'show'])->name('show');
        Route::get('/edit/{id}',[EmployeeAccountController::class,'edit'])->name('edit');
        Route::post('/update/{id}',[EmployeeAccountController::class,'update'])->name('update');
        Route::post('/toggle-status/{id}',[EmployeeAccountController::class,'toggleStatus'])->name('toggle-status');
        Route::post('/delete/{id}',[EmployeeAccountController::class,'destroy'])->name('delete');
    });


/*
|--------------------------------------------------------------------------
| income-category
|--------------------------------------------------------------------------
*/
Route::prefix('income-categories')->name('income-category.')->controller(IncomeCategoryController::class)
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::post('/delete/{id}', 'destroy')->name('delete');
});



/*
|--------------------------------------------------------------------------
| income
|--------------------------------------------------------------------------
*/


Route::prefix('income')
->name('income.')
->controller(IncomeController::class)
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::post('/cancel/{id}', 'cancel')->name('cancel');
    Route::get('/print', 'print')->name('print');
    Route::get('/print/{id}', 'printVoucher')->name('print-voucher');
});


          // janjorl Final
    Route::prefix('journals')->name('journal.')->controller(JournalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/reverse/{id}', 'reverse')->name('reverse');
        Route::get('/print', 'print')->name('print');
        Route::get('/print/{id}', 'printVoucher')->name('print-voucher');
    });

    Route::prefix(
    'contras'
)

->name(
    'contra.'
)

->group(function(){

    Route::get(
        '/',
        [ContraController::class,'index']
    )->name('index');

    Route::get(
        '/create',
        [ContraController::class,'create']
    )->name('create');

    Route::post(
        '/store',
        [ContraController::class,'store']
    )->name('store');

    Route::get(
        '/show/{id}',
        [ContraController::class,'show']
    )->name('show');

    Route::get(
        '/edit/{id}',
        [ContraController::class,'edit']
    )->name('edit');

    Route::post(
        '/update/{id}',
        [ContraController::class,'update']
    )->name('update');

    Route::post(
        '/delete/{id}',
        [ContraController::class,'destroy'
    ])->name('delete');

    Route::get(
        '/print',
        [ContraController::class,'print']
    )->name('print');

});

Route::prefix('salary-sheets')
    ->name('salary-sheets.')
    ->group(function () {

    Route::get('/',
        [SalarySheetController::class, 'index']
    )->name('index');

    Route::get('/create',
        [SalarySheetController::class, 'create']
    )->name('create');

    Route::post('/store',
        [SalarySheetController::class, 'store']
    )->name('store');

    Route::get('/show/{id}',
        [SalarySheetController::class, 'show']
    )->name('show');

    Route::get('/edit/{id}',
        [SalarySheetController::class, 'edit']
    )->name('edit');

    Route::post('/update/{id}',
        [SalarySheetController::class, 'update']
    )->name('update');

    Route::post('/cancel/{id}',
        [SalarySheetController::class, 'cancel']
    )->name('cancel');

    Route::get('/print',
        [SalarySheetController::class, 'print']
    )->name('print');

});

Route::prefix('employee-payments')
    ->name('employee-payment.')
    ->group(function () {

    Route::get('/',
        [EmployeePaymentController::class, 'index']
    )->name('index');

    Route::get('/print-list',
        [EmployeePaymentController::class, 'printList']
    )->name('print-list');

    Route::get('/create',
        [EmployeePaymentController::class, 'create']
    )->name('create');

    Route::post('/store',
        [EmployeePaymentController::class, 'store']
    )->name('store');

    Route::get('/show/{id}',
        [EmployeePaymentController::class, 'show']
    )->name('show');

    Route::get('/edit/{id}',
        [EmployeePaymentController::class, 'edit']
    )->name('edit');

    Route::post('/update/{id}',
        [EmployeePaymentController::class, 'update']
    )->name('update');

    Route::post('/cancel/{id}',
        [EmployeePaymentController::class, 'cancel']
    )->name('cancel');

    Route::get('/print/{id}',
        [EmployeePaymentController::class, 'print']
    )->name('print');

});

Route::prefix('employee-ledger')
    ->name('employee-ledger.')
    ->group(function () {
        Route::get('/{id}', [EmployeeLedgerController::class, 'show'])->name('show');
    });

Route::prefix('payroll-register')
    ->name('payroll-register.')
    ->group(function () {
        Route::get('/', [PayrollRegisterController::class, 'index'])->name('index');
        Route::get('/print', [PayrollRegisterController::class, 'print'])->name('print');
    });
    });

Route::get(
    '/account-transaction',
    [AccountTransactionController::class,'index']
)
->name('account-transaction.index');

Route::get(
    '/account-transaction/{id}',
    [AccountTransactionController::class,'show']
)
->name('account-transaction.show');



});
