<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Account;

use App\Models\SalesInvoice;

use App\Models\PurchaseInvoice;
use App\Models\Income;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Services\HrPayrollSummaryService;

class DashboardController extends Controller
{
    public function __construct(
        private HrPayrollSummaryService $hrPayrollSummaryService
    ) {
    }

public function index()
{

$companyId = auth()->user()->company_id;

$activeFy = FinancialYear::where('company_id', $companyId)
    ->where('is_active', 1)
    ->first();

$incomeTotalQuery = Income::where('company_id', $companyId)
    ->where('status', Income::STATUS_ACTIVE);

if ($activeFy) {
    $incomeTotalQuery->where('financial_year_id', $activeFy->id);
}

$incomeTotal = $incomeTotalQuery->sum('amount');

$expenseTotalQuery = Expense::where('company_id', $companyId)
    ->where('status', Expense::STATUS_ACTIVE);

if ($activeFy) {
    $expenseTotalQuery->where('financial_year_id', $activeFy->id);
}

$expenseTotal = $expenseTotalQuery->sum('amount');


$salesChart = SalesInvoice::

where('company_id',$companyId)

->where('status', 1)

->whereMonth(
'created_at',
now()->month
)

->selectRaw(
'DATE(created_at) as day'
)

->selectRaw(
'SUM(grand_total) as total'
)

->groupBy('day')

->orderBy('day')

->get();



$purchaseChart = PurchaseInvoice::

where('company_id',$companyId)

->whereMonth(
'created_at',
now()->month
)

->selectRaw(
'DATE(created_at) as day'
)

->selectRaw(
'SUM(grand_total) as total'
)

->groupBy('day')

->orderBy('day')

->get();



$recentSales =

SalesInvoice::

where(
'company_id',
$companyId
)

->latest()

->take(5)

->get();



$recentPurchases =

PurchaseInvoice::

where(
'company_id',
$companyId
)

->latest()

->take(5)

->get();



$lowStock =

Product::

where(
'company_id',
$companyId
)

->whereColumn(
'current_stock',
'<=',
'stock_alert'
)

->take(10)

->get();



$staffActivity=

User::

where(
'company_id',
$companyId
)

->latest(
'last_seen'
)

->take(5)

->get();



$data=[

'products'=>

Product::where(
'company_id',
$companyId
)->count(),

'customer_due'=>

SalesInvoice::

where(
'company_id',
$companyId
)

->where('status', 1)

->sum(
'due_amount'
),



'supplier_due'=>

PurchaseInvoice::

where(
'company_id',
$companyId
)

->sum(
'due_amount'
),

'customers'=>

Customer::where(
'company_id',
$companyId
)->count(),


'suppliers'=>

Supplier::where(
'company_id',
$companyId
)->count(),


'staff'=>

User::where(
'company_id',
$companyId
)->count(),


'sales'=>

SalesInvoice::where(
'company_id',
$companyId
)->count(),


'purchases'=>

PurchaseInvoice::where(
'company_id',
$companyId
)->count(),


'cash'=>

Account::where(
'company_id',
$companyId
)

->where(
'account_type',
'cash'
)

->sum(
'current_balance'
),


'bank'=>

Account::where(
'company_id',
$companyId
)

->where(
'account_type',
'bank'
)

->sum(
'current_balance'
),


'stock_items'=>

Product::where(
'company_id',
$companyId
)

->sum(
'current_stock'
),

'income_total' => $incomeTotal,

'expense_total' => $expenseTotal,

];

$hrSummary = $this->hrPayrollSummaryService->summary($companyId, $activeFy);

return view(

'company.dashboard',

compact(

'data',

'hrSummary',

'salesChart',

'purchaseChart',

'recentSales',

'recentPurchases',

'lowStock',

'staffActivity'

)

);

}




}