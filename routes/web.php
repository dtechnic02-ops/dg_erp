

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Company;
use App\Models\Plan;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\CompanyApprovalController;
use App\Http\Controllers\Admin\PaymentApprovalController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
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
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\Company\CashAccountController;
use App\Http\Controllers\Company\PurchaseController;
use App\Http\Controllers\Company\VatController;
use App\Http\Controllers\Company\SalesController;
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
use App\Http\Controllers\Company\EmployeeAccountController;
use App\Http\Controllers\Company\IncomeController;
use App\Http\Controllers\Company\IncomeCategoryController;
use App\Http\Controllers\Company\JournalController;
use App\Http\Controllers\Company\FinancialYearController;
use App\Http\Controllers\Company\ContraController;
use App\Http\Controllers\Company\VatReportController;
use App\Http\Controllers\Company\SalarySheetController;
use App\Http\Controllers\Company\AccountTransactionController;
use App\Http\Controllers\Company\SupplierLedgerController;
use App\Http\Controllers\Company\MaintenanceController;
use App\Http\Controllers\Company\SupplierStatementController;
use App\Http\Controllers\Company\CustomerStatementController;



Route::get('/login', fn() => view('login'))->name('login');

Route::post('/login', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email','password'))) {

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->account_status === 'blocked') {
            Auth::logout();
            return back()->with('error', 'Account blocked');
        }

        if (!$user->role_id) {
            Auth::logout();
            return back()->with('error', 'No role assigned');
        }

        if ($user->role_id == 2) {
            $company = \App\Models\Company::find($user->company_id);

            if ($company) {
                if ($company->expiry_date && now()->gt($company->expiry_date)) {
                    Auth::logout();
                    return back()->with('error', 'Plan expired');
                }

                if ($company->status == 'blocked') {
                    Auth::logout();
                    return back()->with('error', 'Company blocked');
                }
            }
        }

        return match($user->role_id) {
            1 => redirect('/admin/dashboard'),
            2 => redirect('/company/dashboard'),
            3 => redirect('/staff/dashboard'),
        };
    }

    return back()->with('error','Invalid Credentials');

})->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();   // 🔥 must
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');




 Route::get('/company/register', [App\Http\Controllers\CompanyRegisterController::class, 'showForm'])->name('company.register');

 Route::post('/company/register', [App\Http\Controllers\CompanyRegisterController::class, 'register'])->name('company.register.post');



//SUPER ADMIN ROUTES

Route::middleware(['auth','role:1,4'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/companies', [CompanyController::class, 'index'])->name('admin.companies');

    Route::get('/company/block/{id}', [CompanyController::class, 'block'])->name('admin.company.block');
    Route::get('/company/unblock/{id}', [CompanyController::class, 'unblock'])->name('admin.company.unblock');
    Route::post('/company/delete/{id}', [CompanyController::class, 'delete'])->name('admin.company.delete');

    // 🔥 // Admin companis  Blande 
    Route::post('/company/limit/{id}', [CompanyController::class, 'updateLimit'])->name('admin.company.limit');
    Route::post('/company/customer-limit/{id}', [CompanyController::class, 'updateCustomerLimit'])->name('admin.company.customer.limit');
    Route::get('/company/reset/{id}', [App\Http\Controllers\Admin\CompanyController::class, 'resetPassword'])->name('admin.company.reset');


    // Plan & payment
Route::post('/plans', [PlanController::class, 'store'])->name('admin.plans.store');
Route::post('/plans/update/{id}', [PlanController::class, 'update'])->name('admin.plans.update');
 Route::get('/plans/delete/{id}', [PlanController::class, 'destroy'])->name('admin.plans.delete');
     //company Rgistetion
         Route::get('/registrations', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'index'])
        ->name('admin.registrations');

    // 🔥 Rajistetion Menejment
    Route::post('/approve/{id}', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'approve'])->name('admin.approve');
    Route::post('/reject/{id}', [App\Http\Controllers\Admin\CompanyApprovalController::class, 'reject'])->name('admin.reject');
    
        //Payments
       Route::get('/payments', [App\Http\Controllers\Admin\PaymentApprovalController::class, 'index'])->name('admin.payments');
       Route::post( '/payment/approve/{id}', [App\Http\Controllers\Admin\PaymentApprovalController::class, 'approve'] )->name('admin.payment.approve');
       Route::post( '/payment/reject/{id}', [App\Http\Controllers\Admin\PaymentApprovalController::class, 'reject'] )->name('admin.payment.reject');
       //Admin Users
        Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users');

        //Manual Payment
    Route::get('/manual-payment', [App\Http\Controllers\Admin\PaymentApprovalController::class, 'manualForm'])->name('admin.manual.payment');
    Route::post('/manual-payment', [App\Http\Controllers\Admin\PaymentApprovalController::class, 'manualStore'])->name('admin.manual.payment.store');
   
    Route::get('/plans', [App\Http\Controllers\Admin\PlanController::class, 'index'])->name('admin.plans');

     // Admin companis  Blande 
      Route::get('/invoice/{id}', [PaymentApprovalController::class, 'invoice'])->name('admin.invoice');
    
      Route::get('/user/block/{id}', [UserController::class, 'block'])->name('admin.user.block');
     Route::get('/user/unblock/{id}', [UserController::class, 'unblock'])->name('admin.user.unblock');
      Route::post('/user/delete/{id}', [UserController::class, 'delete'])->name('admin.user.delete');
      Route::get('/user/reset/{id}', [AdminUserController::class, 'reset'])->name('admin.user.reset');
});


Route::middleware(['auth','role:2',\App\Http\Middleware\UpdateLastSeen::class])->prefix('company')->name('company.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile',[\App\Http\Controllers\Company\CompanyClientController::class, 'profile'])->name('profile');

    Route::post('/profile/update',[\App\Http\Controllers\Company\CompanyClientController::class, 'update'])->name('profile.update');
    
  
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

        Route::get('/',[UserController::class, 'index'])->name('index');
        Route::post('/store',[UserController::class, 'store'])->name('store');
        Route::get('/edit/{id}',[UserController::class, 'edit'])->name('edit');
        Route::post('/update/{id}',[UserController::class, 'update'])->name('update');
        Route::get('/block/{id}',[UserController::class, 'block'])->name('block');
        Route::get('/unblock/{id}',[UserController::class, 'unblock'] )->name('unblock');
        Route::post('/delete/{id}',[UserController::class, 'destroy'])->name('delete');
        Route::get('/reset/{id}',[UserController::class, 'resetPassword'])->name('reset');

    });

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/permissions',[UserController::class, 'permissionPage'])->name('permissions.index');
    Route::post('/permissions',[UserController::class, 'updateRolePermission'])->name('permissions.update');

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
Route::post(
    '/{id}/cancel',
    [PurchaseController::class, 'cancel']
)->name('cancel');

     Route::get(
    '/print',
    [PurchaseController::class, 'print']
)->name('print');
    
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

        Route::get('/print',[PurchasePaymentController::class, 'printList'])->name('print');
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
        Route::get(
    'print',
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
Route::get(
    '/print',
    [PurchaseReturnRefundController::class,'print']
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

Route::prefix(
'expense-categories'
)

->name(
'expense-category.'
)

->group(function(){

Route::get(
'/',
[ExpenseCategoryController::class,'index']
)->name('index');

Route::get(
'/create',
[ExpenseCategoryController::class,'create']
)->name('create');

Route::post(
'/store',
[ExpenseCategoryController::class,'store']
)->name('store');

});


/*
|--------------------------------------------------------------------------
| EXPENSES
|--------------------------------------------------------------------------
*/

Route::prefix('expenses')->name('expense.')->group(function(){
Route::get('/',[ExpenseController::class,'index'])->name('index');
Route::get('/create',[ExpenseController::class,'create'])->name('create');
Route::post('/store',[ExpenseController::class,'store'])->name('store');
Route::get('/show/{id}',[ExpenseController::class,'show'])->name('show');
Route::get('/print',[ExpenseController::class,'print'])->name('print');
Route::get('/edit/{id}',[ExpenseController::class,'edit'])->name('edit');
Route::post('/update/{id}',[ExpenseController::class,'update'])->name('update');
Route::delete('/delete/{id}',[ExpenseController::class,'destroy'])->name('delete');

});

/*
|--------------------------------------------------------------------------
| LOAN ACCOUNTS
|--------------------------------------------------------------------------
*/

Route::prefix('loan-accounts')->name('loan-account.')->group(function(){
      Route::get('/',[LoanAccountController::class,'index'])->name('index');
      Route::get('/create',[LoanAccountController::class,'create'])->name('create');
      Route::post('/store',[LoanAccountController::class,'store'])->name('store');
      Route::get('/show/{id}',[LoanAccountController::class,'show'])->name('show');
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

});

/*
|--------------------------------------------------------------------------
| PARTY ACCOUNTS
|--------------------------------------------------------------------------
*/
    Route::prefix('party-accounts')->name('party-account.')->group(function(){
        Route::get('/',[PartyAccountController::class,'index'])->name('index');
        Route::get('/create',[PartyAccountController::class,'create'])->name('create');
        Route::post('/store',[PartyAccountController::class,'store'])->name('store');
        Route::get('/show/{id}',[PartyAccountController::class,'show'])->name('show');

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

/*
|--------------------------------------------------------------------------
| Empulay Account
|--------------------------------------------------------------------------
*/
    Route::prefix('employee-accounts')->name('employee-account.')->group(function(){
        Route::get('/',[EmployeeAccountController::class,'index'])->name('index');
        Route::get('/create',[EmployeeAccountController::class,'create'])->name('create');
        Route::post('/store',[EmployeeAccountController::class,'store'])->name('store');
        Route::get('/show/{id}',[EmployeeAccountController::class,'show'])->name('show');
        Route::get('/edit/{id}',[EmployeeAccountController::class,'edit'])->name('edit');
        Route::post('/update/{id}',[EmployeeAccountController::class,'update'])->name('update');
        Route::post('/delete/{id}',[EmployeeAccountController::class,'destroy'])->name('delete');
    });


/*
|--------------------------------------------------------------------------
| income-category
|--------------------------------------------------------------------------
*/
Route::prefix('income-categories')->name('income-category.')->controller(IncomeCategoryController::class)
->group(function(){
Route::get('/','index')->name('index');Route::get('/create','create'
)->name(
'create'
);

Route::post(
'/store',
'store'
)->name(
'store'
);

Route::post(
'/delete/{id}',
'destroy'
)->name(
'delete'
);

});



/*
|--------------------------------------------------------------------------
| income
|--------------------------------------------------------------------------
*/


Route::prefix('income')
->name('income.')
->controller(IncomeController::class)
->group(function(){

Route::get(
'/',
'index'
)->name(
'index'
);

Route::get(
'/create',
'create'
)->name(
'create'
);

Route::post(
'/store',
'store'
)->name(
'store'
);

Route::get(
'/show/{id}',
'show'
)->name(
'show'
);

Route::post(
'/delete/{id}',
'destroy'
)->name(
'delete'
);
Route::get(
'/edit/{id}',
'edit'
)->name('edit');

Route::post(
'/update/{id}',
'update'
)->name('update');

Route::get(
'/print',
'print'
)->name('print');

    });


          // janjorl Final
    Route::prefix('journals')->name('journal.')->controller(JournalController::class)->group(function(){
         Route::get('/','index')->name('index');Route::get('/create','create')->name('create');
         Route::post('/store','store')->name('store');Route::get('/show/{id}','show')->name('show');
         Route::get('/edit/{id}','edit')->name('edit');Route::post('/update/{id}','update')->name('update');
         Route::post('/delete/{id}','destroy')->name('delete');Route::get('/print','print')->name('print');
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

    Route::post('/delete/{id}',
        [SalarySheetController::class, 'destroy']
    )->name('delete');

    Route::get('/print',
        [SalarySheetController::class, 'print']
    )->name('print');

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





//STAFF ROUTES
Route::middleware(['auth','role:3'])->prefix('staff')->group(function () {

    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index']);

    // Example modules (permission-based later)
    Route::get('/invoice', function () {
        return view('staff.invoice');
    });

    Route::get('/inventory', function () {
        return view('staff.inventory');
    });
    // SERVICES



});