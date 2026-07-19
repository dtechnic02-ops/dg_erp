<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\FileUploadService;
use App\Services\ValidationService;

class BrandController extends Controller
{

/* =====================

SHARED FILTER QUERY

Used by both index() and print() so the
Print action always respects the same
filters as the current list view.

===================== */

private function filteredBrandQuery(
Request $request
){

$query = Brand::where(

'company_id',

auth()->user()->company_id

);


if(
$request->search
){

$query->where(

'name',

'like',

'%'.$request->search.'%'

);

}


return $query;

}


/* =====================

BRAND IMAGE FOLDER

Shared upload path used by store()
and update() so the folder logic
exists in one place only.

===================== */

private function brandImageFolder()
{

return

'companies/'.

auth()->user()->company_id.

'/brands';

}


/* =====================

INDEX

===================== */

public function index(
Request $request
){

$totalBrands=

$this->filteredBrandQuery($request)->count();


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


$brands=

$this->filteredBrandQuery($request)

->latest()

->paginate($perPage)

->withQueryString();


return view(

'company.brands.index',

compact(
'brands',
'totalBrands',
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

Validator::make(
$request->all(),
[

    'name'=>[

        'required',

        Rule::unique(
            'brands',
            'name'
        )->where(

            fn ($q) =>

            $q->where(
                'company_id',
                auth()->user()->company_id
            )

        ),

    ],

    'image'=>

    ValidationService::document(),

]
)->validate();


$data=[

'company_id'=>

auth()->user()->company_id,

'created_by' => auth()->id(),

'name'=>

$request->name,

'description'=>

$request->description,

'status'=>

$request->status
?? 1,

'image'=>

FileUploadService::replaceFile(

    $request,

    'image',

    null,

    $this->brandImageFolder()

),

];


Brand::create(
    $data
);

return back()

->with(

'success',

'Brand Added'

);

}


/* =====================

UPDATE

===================== */

public function update(
Request $request,
$id
){

$brand=

Brand::where(

'id',

$id

)

->where(

'company_id',

auth()->user()->company_id

)

->firstOrFail();


Validator::make(
$request->all(),
[

    'name'=>[

        'required',

        Rule::unique(
            'brands',
            'name'
        )
        ->ignore(
            $brand->id
        )
        ->where(

            fn ($q) =>

            $q->where(
                'company_id',
                auth()->user()->company_id
            )

        ),

    ],

    'image'=>

    ValidationService::document(),

]
)->validate();


$brand->update([

'name'=>

$request->name,

'description'=>

$request->description,

'status'=>

$request->status
?? 1,

'updated_by' => auth()->id(),

'image'=>

FileUploadService::replaceFile(

    $request,

    'image',

    $brand->image,

    $this->brandImageFolder()

),

]);


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

$brand=

Brand::where(

'id',

$id

)

->where(

'company_id',

auth()->user()->company_id

)

->firstOrFail();


FileUploadService::deleteFile(
$brand->image
);


$brand->delete();


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

    $brand = Brand::where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    return view(

        'company.brands.show',

        compact(

            'brand'

        )

    );
}

/* =====================

BRAND PROFILE PRINT

===================== */

public function printProfile($id)
{
    $companyId =
        auth()->user()->company_id;

    $brand = Brand::where(
        'company_id',
        $companyId
    )
    ->findOrFail($id);

    $print = true;

    return view(

        'company.brands.show',

        compact(

            'brand',

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

$brands=

$this->filteredBrandQuery($request)

->latest()

->get();


$totalBrands=

$brands->count();


return view(

'company.brands.print',

compact(
'brands',
'totalBrands'
)

);

}

}
