<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Services\CustomerTransactionService;
use App\Services\ValidationService;

class CustomerController extends Controller
{

/* =====================

INDEX

===================== */

/* =====================

SHARED FILTER QUERY

Used by both index() and print() so the
Print action always respects the same
filters as the current list view.

===================== */

private function filteredCustomerQuery(
Request $request
){

$query = Customer::where(

'company_id',

auth()->user()->company_id

);


if(
$request->search
){

$query->where(

function($q)
use(
$request
){

$q->where(

'name',

'like',

'%'.$request->search.'%'

)

->orWhere(

'mobile',

'like',

'%'.$request->search.'%'

);

}

);

}


return $query;

}


public function index(
Request $request
){

$totalCurrentBalance=

$this->filteredCustomerQuery($request)->sum('current_balance');


/* =====================

PER PAGE (MASTER PAGINATION)

Allowed values only.
Any other value falls back to 10.

===================== */

$allowedPerPage = [10, 25, 50, 100, 200, 500];

$perPage = (int) $request->get('per_page', 10);

if (!in_array($perPage, $allowedPerPage)) {

    $perPage = 10;

}


$customers=

$this->filteredCustomerQuery($request)

->latest()

->paginate($perPage)

->withQueryString();


return view(

'company.customers.index',

compact(
'customers',
'totalCurrentBalance',
'perPage'
)

);

}


/* =====================

STORE

===================== */

public function store(
Request $request
){

$request->validate([
    'credit_days' => ValidationService::quantity(),
]);

$data=[

'company_id'=>


auth()->user()->company_id,
'created_by' => auth()->id(),
'name'=>

$request->name,

'authority_name'=>

$request->authority_name,

'mobile'=>

$request->mobile,

'telephone'=>

$request->telephone,

'fax_no'=>

$request->fax_no,

'email'=>

$request->email,

'website'=>

$request->website,

'address'=>

$request->address,

'tax_no'=>

$request->tax_no,

'opening_balance'=>

$request->opening_balance
?? 0,

'credit_days'=>

max(0, (int) ($request->credit_days ?? 0)),

'current_balance'=>

$request->opening_balance
?? 0,

'bank_name'=>

$request->bank_name,

'bank_account_no'=>

$request->bank_account_no,

'note'=>

$request->note,

'status'=>

$request->status
?? 'active'

];


/* IMAGE UPLOAD */

if(
$request->hasFile(
'image'
)
){

$request->validate([

'image'=>[

'file',

'mimes:jpg,jpeg,png,pdf',

'max:10240'

]

], [

'image.max'=>

'Maximum file size is 10 MB.',

'image.mimes'=>

'Only JPG PNG PDF allowed.'

]);


$file=
$request->file(
'image'
);


$folder=

public_path(

'companies/'.

auth()->user()->company_id.

'/customers'

);


if(
!is_dir(
$folder
)
){

mkdir(

$folder,

0755,

true

);

}




$name=

time()

.'_'

.uniqid()

.'.'

.$file->getClientOriginalExtension();


$file->move(

$folder,

$name

);


$data[
'image_path'
]=

'companies/'.

auth()->user()->company_id.

'/customers/'.

$name;

}




$customer = Customer::create(
    $data
);
if (
    $customer->opening_balance > 0
)
{
    $activeFy = FinancialYear::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'is_active',
        1
    )
    ->firstOrFail();

    CustomerTransactionService::createTransaction([

        'company_id'        =>
            auth()->user()->company_id,

        'financial_year_id' =>
            $activeFy->id,

        'customer_id'       =>
            $customer->id,

        'transaction_date'  =>
            $activeFy->start_date,

        'voucher_no'        =>
            'OPEN-' . $customer->id,

        'reference_type'    =>
            'opening_balance',

        'reference_id'      =>
            $customer->id,

        'reference_no'      =>
            'OPEN-' . $customer->id,

        'description'       =>
            'Customer Opening Balance',

        'debit'             =>
            $customer->opening_balance,

        'credit'            =>
            0,

        'created_by'        =>
            auth()->id(),

        'status'            =>
            1,

    ]);
}

return back()

->with(

'success',

'Customer Added'

);

}



/* =====================

UPDATE

===================== */

public function update(
Request $request,
$id
){

$customer=

Customer::where(

'id',

$id

)

->where(

'company_id',

auth()->user()->company_id

)

->firstOrFail();

$request->validate([
    'credit_days' => ValidationService::quantity(),
]);

$customer->update([

'name'=>

$request->name,

'authority_name'=>

$request->authority_name,

'mobile'=>

$request->mobile,

'telephone'=>

$request->telephone,

'fax_no'=>

$request->fax_no,

'email'=>

$request->email,

'website'=>

$request->website,

'address'=>

$request->address,

'tax_no'=>

$request->tax_no,

'credit_days'=>

max(0, (int) ($request->credit_days ?? 0)),

'opening_balance' =>

$customer->opening_balance,

'current_balance'=>

$customer->current_balance,

'bank_name'=>

$request->bank_name,

'bank_account_no'=>

$request->bank_account_no,

'note'=>

$request->note,

'status'=>

$request->status
?? 'active'

]);


/* IMAGE UPDATE */


if(
$request->hasFile(
'image'
)
){
$request->validate([

'image'=>[
'file',
'mimes:jpg,jpeg,png,pdf',
'max:10240'
]

],[

'image.max'=>

'Maximum file size is 10 MB.',

'image.mimes'=>

'Only JPG PNG PDF allowed.'

]);


if(

$customer->image_path &&

file_exists(

public_path(

$customer->image_path

)

)

){

unlink(

public_path(

$customer->image_path

)

);

}


$file=
$request->file(
'image'
);


$folder=

public_path(

'companies/'.

auth()->user()->company_id.

'/customers'

);


if(
!is_dir(
$folder
)
){

mkdir(

$folder,

0755,

true

);

}


$name=

time()

.'_'

.uniqid()

.'.'

.$file->getClientOriginalExtension();


$file->move(

$folder,

$name

);


$customer->update([

'image_path'=>

'companies/'.

auth()->user()->company_id.

'/customers/'.

$name

]);

}



return back()

->with(

'success',

'Updated'

);

}




/* =====================

DELETE

===================== */

public function destroy(
$id
){

$customer=

Customer::where(

'id',

$id

)

->where(

'company_id',

auth()->user()->company_id

)

->firstOrFail();


if(

$customer->image_path &&

file_exists(

public_path(

$customer->image_path

)

)

){

unlink(

public_path(

$customer->image_path

)

);

}


$customer->delete();


return back()

->with(

'success',

'Deleted'

);

}



public function show($id)
{
    $companyId =
        auth()->user()->company_id;

    $customer = Customer::where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    return view(

        'company.customers.show',

        compact(

            'customer'

        )

    );
}

/* =====================

CUSTOMER PROFILE PRINT

===================== */

public function printProfile($id)
{
    $companyId =
        auth()->user()->company_id;

    $customer = Customer::where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    $print = true;

    return view(

        'company.customers.show',

        compact(

            'customer',

            'print'

        )

    );
}

/* =====================

PRINT

===================== */

public function print(
Request $request
){

$customers=

$this->filteredCustomerQuery($request)

->latest()

->get();


$totalCustomers=

$customers->count();


$totalOpeningBalance=

$customers->sum('opening_balance');


$totalCurrentBalance=

$customers->sum('current_balance');


return view(

'company.customers.print',

compact(
'customers',
'totalCustomers',
'totalOpeningBalance',
'totalCurrentBalance'
)

);

}

}
