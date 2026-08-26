<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Services\ValidationService;
use App\Services\WhatsappShareService;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use AuthorizesCompanyPermission;

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
    $this->authorizeCompanyPermission('view_customer');

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
    $this->authorizeCompanyPermission('create_customer');

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
0,

'credit_days'=>

max(0, (int) ($request->credit_days ?? 0)),

'current_balance'=>
0,

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




$customer = DB::transaction(function () use ($data) {
    return Customer::create(
        $data
    );
});

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
    $this->authorizeCompanyPermission('edit_customer');

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
    $this->authorizeCompanyPermission('delete_customer');

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

if ($customer->transactions()->exists() || \App\Models\OpeningBalanceLine::where('subledger_type', 'customer')->where('subledger_id', $customer->id)->whereHas('openingBalance', fn ($q) => $q->where('company_id', auth()->user()->company_id))->exists() || \App\Models\AccountingEntryLine::where('subledger_type', 'customer')->where('subledger_id', $customer->id)->exists()) {
    return back()->with('error', 'Customer has financial history and cannot be deleted. Deactivate the customer instead.');
}


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
    $this->authorizeCompanyPermission('view_customer');

    $companyId =
        auth()->user()->company_id;

    $customer = Customer::where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    $whatsappShareEnabled = app(WhatsappShareService::class)->isEnabled(auth()->user()->company);

    return view(

        'company.customers.show',

        compact(

            'customer',
            'whatsappShareEnabled'

        )

    );
}

public function whatsappShare($id, WhatsappShareService $whatsappShareService)
{
    $this->authorizeCompanyPermission('view_customer');
    $company = auth()->user()->company;
    $customer = Customer::where('company_id', $company->id)->findOrFail($id);

    return redirect()->away($whatsappShareService->customerShareUrl(
        $company,
        $customer,
        $customer->current_balance
    ));
}

/* =====================

CUSTOMER PROFILE PRINT

===================== */

public function printProfile($id)
{
    $this->authorizeCompanyPermission('print_customer');

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
    $this->authorizeCompanyPermission('print_customer');

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
