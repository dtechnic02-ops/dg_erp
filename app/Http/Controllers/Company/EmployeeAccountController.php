<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeAccount;
use App\Services\ValidationService;
use App\Services\FileUploadService;

class EmployeeAccountController extends Controller

{

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/
public function index(Request $request)
{

$query =

EmployeeAccount::where(

'company_id',

auth()->user()->company_id

);

if(

$request->search

){

$query->where(function($q)
use($request){

$q->where(

'employee_code',

'like',

'%'.$request->search.'%'

)

->orWhere(

'first_name',

'like',

'%'.$request->search.'%'

)

->orWhere(

'phone',

'like',

'%'.$request->search.'%'

)

->orWhere(

'designation',

'like',

'%'.$request->search.'%'

);

});

}

$employees =

$query

->latest()

->paginate(20)

->withQueryString();

return view(

'company.employee-account.index',

compact(

'employees'

)

);

}


/*
|--------------------------------------------------------------------------
| CREATE
|--------------------------------------------------------------------------
*/

public function create()
{

$companyId =
auth()->user()->company_id;

$year =
now()->year;

$last =
EmployeeAccount::where(
'company_id',
$companyId
)

->latest('id')

->first();

$next=1;

if($last){

preg_match(
'/(\d+)$/',
$last->employee_code,
$match
);

$next =
isset($match[1])

?

((int)$match[1])+1

:

1;

}

$employeeCode =

'EMP-'

.$companyId

.'-'

.$year

.'-'

.str_pad(
$next,
4,
'0',
STR_PAD_LEFT
);

return view(
'company.employee-account.create',
compact(
'employeeCode'
)
);

}


/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

public function store(Request $request)
{

$request->validate([

    'employee_code' =>
    'required|unique:employee_accounts,employee_code',

    'first_name' =>
    'required',

    'email' =>
    ValidationService::email(),
    'phone' =>
ValidationService::phone(),

'emergency_phone' =>
ValidationService::phone(),

    'dob' =>
    'nullable|date',

    'joining_date' =>
    'required|date',

    'basic_salary' =>
    ValidationService::amount(),

    'opening_due_salary' =>
    ValidationService::amount(),

    'photo' =>
    ValidationService::image(),

    'cv_attachment' =>
    ValidationService::document(),

    'id_document' =>
    ValidationService::document(),

    'contract_document' =>
    ValidationService::document(),

]);

$companyId =
auth()->user()->company_id;

$folder =

'companies/'

.$companyId

.'/employees';





/*
UPLOADS
*/

$photo=null;

$cv=null;

$idDocument=null;

$contract=null;

$files=[

'photo',

'cv_attachment',

'id_document',

'contract_document'

];

foreach($files as $field){

    if($request->hasFile($field)){

        if($field == 'photo'){

            $photo =
                FileUploadService::uploadImage(
                    $request->file('photo'),
                    $folder,
                    800
                );

        }
        
        elseif($field == 'cv_attachment'){

            $cv =
                FileUploadService::uploadFile(
                    $request->file('cv_attachment'),
                    $folder
                );

        }
        elseif($field == 'id_document'){

            $idDocument =
                FileUploadService::uploadFile(
                    $request->file('id_document'),
                    $folder
                    
                );
                

        }
        elseif($field == 'contract_document'){

            $contract =
                FileUploadService::uploadFile(
                    $request->file('contract_document'),
                    $folder
                    
                );

        }

    }

}
try
{
DB::transaction(function() use(

$request,
$companyId,
$photo,
$cv,
$idDocument,
$contract

){

EmployeeAccount::create([

'company_id'=>

$companyId,

'employee_code'=>

$request->employee_code,

'first_name'=>

$request->first_name,

'middle_name'=>

$request->middle_name,

'last_name'=>

$request->last_name,

'phone'=>

$request->phone,

'email'=>

$request->email,

'address'=>

$request->address,

'gender'=>

$request->gender,

'dob'=>

$request->dob,

'joining_date'=>

$request->joining_date,

'designation'=>

$request->designation,

'department'=>

$request->department,

'post'=>

$request->post,

'employment_type'=>

$request->employment_type

??

'permanent',

'basic_salary'=>

(float)

($request->basic_salary ?? 0),

'salary_type'=>

$request->salary_type

??

'monthly',

'opening_due_salary'=>

(float)

($request->opening_due_salary ?? 0),

'bank_name'=>

$request->bank_name,

'bank_account_no'=>

$request->bank_account_no,

'account_holder_name'=>

$request->account_holder_name,

'cit_no'=>

$request->cit_no,

'pan_no'=>

$request->pan_no,

'emergency_contact'=>

$request->emergency_contact,

'emergency_phone'=>

$request->emergency_phone,

'photo'=>

$photo,

'cv_attachment'=>

$cv,

'id_document'=>

$idDocument,

'contract_document'=>

$contract,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

});

 return redirect()

->route(
'company.employee-account.index'
)

->with(
'success',
'Employee created successfully.'
);

    }
    catch (\Exception $e) {

        return back()
            ->withInput()
            ->with(
                'error',
                $e->getMessage()
            );

    }
}


/*
|--------------------------------------------------------------------------
| Edit Method
|--------------------------------------------------------------------------
*/
public function edit($id)
{

$employee=

EmployeeAccount::where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.employee-account.edit',

compact(

'employee'

)

);

}


/*
|--------------------------------------------------------------------------
| Delete Method
|--------------------------------------------------------------------------
*/
public function destroy($id)
{
    try {

        $employee =

        EmployeeAccount::where(

            'company_id',

            auth()->user()->company_id

        )

        ->findOrFail($id);

        $files = [

            $employee->photo,

            $employee->cv_attachment,

            $employee->id_document,

            $employee->contract_document

        ];

        DB::transaction(function ()
        use (
            $employee,
            $files
        ) {

            foreach ($files as $file) {

                FileUploadService::deleteFile(
                    $file
                );

            }

            $employee->delete();

        });

        return back()

        ->with(

            'success',

            'Deleted.'

        );

    }
    catch (\Exception $e) {

        return back()

        ->with(

            'error',

            $e->getMessage()

        );

    }
}



/*
|--------------------------------------------------------------------------
   update
|--------------------------------------------------------------------------
*/

public function update(
Request $request,
$id
)
{

$request->validate([

'first_name'=>'required',
'email' => ValidationService::email(),
'phone' =>
ValidationService::phone(),

'emergency_phone' =>
ValidationService::phone(),
'basic_salary' =>
ValidationService::amount(),

'opening_due_salary' =>
ValidationService::amount(),

'dob' =>
'nullable|date',

'joining_date' =>
'required|date',
'photo' => ValidationService::image(51200),

'cv_attachment' => ValidationService::document(51200),

'id_document' => ValidationService::document(51200),

'contract_document' => ValidationService::document(51200),


]);

$employee=

EmployeeAccount::where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);


$companyId=

auth()->user()->company_id;

$folder=

'companies/'

.$companyId

.'/employees';


try {
DB::transaction(function()

use(

$request,
$employee,
$folder

){

$photo=
$employee->photo;

$cvAttachment=
$employee->cv_attachment;

$idDocument=
$employee->id_document;

$contractDocument=
$employee->contract_document;


/*
PHOTO
*/
$photo = FileUploadService::replaceImage(
    $request,
    'photo',
    $photo,
    $folder,
    800
);

$cvAttachment = FileUploadService::replaceFile(
    $request,
    'cv_attachment',
    $cvAttachment,
    $folder
);

$idDocument = FileUploadService::replaceFile(
    $request,
    'id_document',
    $idDocument,
    $folder
);

$contractDocument = FileUploadService::replaceFile(
    $request,
    'contract_document',
    $contractDocument,
    $folder
);


$employee->update([

'first_name'=>$request->first_name,

'middle_name'=>$request->middle_name,

'last_name'=>$request->last_name,

'phone'=>$request->phone,

'email'=>$request->email,

'address'=>$request->address,

'gender'=>$request->gender,

'dob'=>$request->dob,

'joining_date'=>$request->joining_date,

'designation'=>$request->designation,

'department'=>$request->department,

'post'=>$request->post,

'employment_type'=>

$request->employment_type

??

'permanent',

'basic_salary'=>

(float)

($request->basic_salary ?? 0),

'salary_type'=>

$request->salary_type

??

'monthly',

'opening_due_salary'=>

(float)

($request->opening_due_salary ?? 0),

'bank_name'=>$request->bank_name,

'bank_account_no'=>$request->bank_account_no,

'account_holder_name'=>$request->account_holder_name,

'cit_no'=>$request->cit_no,

'pan_no'=>$request->pan_no,

'emergency_contact'=>$request->emergency_contact,

'emergency_phone'=>$request->emergency_phone,

'photo'=>$photo,

'cv_attachment'=>$cvAttachment,

'id_document'=>$idDocument,

'contract_document'=>$contractDocument,

'note'=>$request->note

]);

});

   return redirect()

->route(
'company.employee-account.index'
)

->with(
'success',
'Employee updated successfully.'
);

}
catch (\Exception $e){

    return back()
        ->withInput()
        ->with(
            'error',
            $e->getMessage()
        );

}
}
/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

public function show($id)
{

$employee=

EmployeeAccount::where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.employee-account.show',

compact(

'employee'

)

);

}

}