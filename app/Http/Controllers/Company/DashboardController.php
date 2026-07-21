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

class DashboardController extends Controller
{

public function index()
{

$companyId = auth()->user()->company_id;


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

];

return view(

'company.dashboard',

compact(

'data',

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