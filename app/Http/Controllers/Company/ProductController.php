<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Services\ValidationService;
use App\Services\StockService;
use App\Models\StockMovement;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Models\FinancialYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // 🔥 PRODUCT LIST

private function filteredProductQuery(Request $request)
{

$query = Product::

where(
'company_id',
auth()->user()->company_id
)

->where(
'status',
'!=',
'inactive'
);



    // 🔍 SEARCH

   if ($request->filled('search'))
    {
        $query->where(function ($q) use ($request) {

$q->where(
    'name',
    'like',
    '%' . $request->search . '%'
)

->orWhere(
    'barcode',
    'like',
    '%' . $request->search . '%'
);

        });
    }

    // 📦 STOCK FILTER

    if ($request->stock_filter == 'out')
    {
        $query->where(
            'current_stock',
            '<=',
            0
        );
    }

    elseif ($request->stock_filter == 'low')
    {
        $query->whereColumn(
            'current_stock',
            '<=',
            'stock_alert'
        )
        ->where(
            'current_stock',
            '>',
            0
        );
    }

    elseif ($request->stock_filter == 'available')
    {
        $query->where(
            'current_stock',
            '>',
            0
        );
    }

    // 🏷️ BRAND FILTER

    if ($request->filled('brand_id'))
    {
        $query->where(
            'brand_id',
            $request->brand_id
        );
    }

    return $query;

}

/* =====================

BRAND DROPDOWN

Shared brand list used by index(), create(),
and edit() so the dropdown source exists in
one place only.

===================== */

private function companyBrands()
{

    return Brand::where(
        'company_id',
        auth()->user()->company_id
    )
    ->orderBy('name')
    ->get();

}

/* =====================

PRODUCT IMAGE FOLDER

Shared upload path used by store()
and update() so the folder logic
exists in one place only.

===================== */

private function productImageFolder()
{

    return

    'companies/'.

    auth()->user()->company_id.

    '/products';

}

public function index(Request $request)
{

    $totalProducts = $this->filteredProductQuery($request)->count();

    $totalStockQuantity = $this->filteredProductQuery($request)->sum('current_stock');

    $totalOutOfStock = $this->filteredProductQuery($request)->where('current_stock', '<=', 0)->count();

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

    // 🔥 FINAL PRODUCTS

  $products = $this->filteredProductQuery($request)
   ->with(['brand'])
   ->latest()
   ->paginate($perPage)
   ->withQueryString();

   $brands = $this->companyBrands();

   return view( 'company.products.index', compact('products', 'totalProducts', 'totalStockQuantity', 'totalOutOfStock', 'brands', 'perPage') );
}



    // 🔥 CREATE FORM

    public function create()
    {
        $units = Unit::where(
            'company_id',
            auth()->user()->company_id
        )->get();

        $categories = ProductCategory::where(
            'company_id',
            auth()->user()->company_id
        )->get();

        $brands = $this->companyBrands();

        return view(
            'company.products.form',
            [
                'product' => null,
                'units' => $units,
                'categories' => $categories,
                'brands' => $brands,
            ]
        );
    }

    // 🔥 STORE PRODUCT
public function store(Request $request)
{

$companyId = auth()->user()->company_id;

$request->validate([

'name' => [

    'required',

    'max:255',

    ValidationService::uniquePerCompany(
        'products',
        'name',
        $companyId
    ),

],

'barcode' => [

    'nullable',

    'max:100',

    ValidationService::uniquePerCompany(
        'products',
        'barcode',
        $companyId
    ),

],

'category_id'=>[
    'required',
    Rule::exists('product_categories', 'id')->where('company_id', $companyId),
],

'unit_id'=>[
    'required',
    Rule::exists('units', 'id')->where('company_id', $companyId),
],

'brand_id'=>[
    'nullable',
    Rule::exists('brands', 'id')->where('company_id', $companyId),
],

'cost_price' =>
ValidationService::requiredAmount(),

'retail_price' =>
ValidationService::requiredAmount(),

'wholesale_price' =>
ValidationService::amount(),

'stock_alert' =>
ValidationService::quantity(),

'opening_stock' =>

ValidationService::amount(),

'batch_no' =>
ValidationService::string(100),

'manufacture_date' =>
ValidationService::date(),

'expiry_date' =>
ValidationService::date(),

'allow_online' =>
ValidationService::boolean(),

'image' =>
ValidationService::image(),

]);


$imagePath =
    FileUploadService::replaceImage(
        $request,
        'image',
        null,
        $this->productImageFolder(),
        800
    );


/* CREATE PRODUCT */
$activeFinancialYear = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    1
)
->first();

if (!$activeFinancialYear)
{
    return back()
        ->withInput()
        ->with(
            'error',
            'Active Financial Year not found.'
        );
}

DB::beginTransaction();

try{

$product=

Product::create([

'company_id'=>

$companyId,
'name'=>

$request->name,

'category_id'=>

$request->category_id,

'brand_id'=>

$request->brand_id,

'unit_id'=>

$request->unit_id,

'barcode' => $request->barcode,

'batch_no' => $request->batch_no,

'manufacture_date' => $request->manufacture_date,

'expiry_date' => $request->expiry_date,

'allow_online' => $request->boolean('allow_online'),

'cost_price'=>

$request->cost_price,

'retail_price'=>

$request->retail_price,

'wholesale_price'=>
$request->wholesale_price
?? $request->retail_price,

'stock_alert'=>

$request->stock_alert ?? 0,



'current_stock'=>0,

'status' => $request->status ?? 'active',

'description'=>

$request->description,

'image'=>

$imagePath

]);


/* OPENING STOCK */

if(
($request->opening_stock ?? 0) > 0
){

StockService::increase(

    $product,

    $request->opening_stock,

    'opening_stock',

    'OPENING',

    $activeFinancialYear->id,

    $activeFinancialYear->start_date

);

}

DB::commit();

return redirect()

->route(
'company.products.index'
)

->with(
'success',
'Product Created Successfully'
);

}

catch(\Exception $e){

DB::rollBack();

FileUploadService::deleteFile(
    $imagePath
);

throw $e;
}
}



public function edit($id)
{

$product=

Product::where(

'id',

$id

)

->where(

'company_id',

auth()->user()->company_id

)

->firstOrFail();


$units=

Unit::where(

'company_id',

auth()->user()->company_id

)

->get();


$categories=

ProductCategory::where(

'company_id',

auth()->user()->company_id

)

->get();


$brands = $this->companyBrands();


return view(

'company.products.form',

compact(

'product',

'units',

'categories',

'brands'

)

);

}

    // 🔥 UPDATE PRODUCT

    public function update(
Request $request,
$id
){

$companyId = auth()->user()->company_id;

$product=

Product::where(

'id',

$id

)

->where(

'company_id',

$companyId

)

->firstOrFail();


$request->validate([

'name' => [

    'required',

    'max:255',

    ValidationService::uniquePerCompany(
        'products',
        'name',
        $companyId,
        $product->id
    ),

],
'barcode' => [

    'nullable',

    'string',

    'max:100',

    ValidationService::uniquePerCompany(
        'products',
        'barcode',
        $companyId,
        $product->id
    ),

],

'category_id'=>[
    'required',
    Rule::exists('product_categories', 'id')->where('company_id', $companyId),
],

'unit_id'=>[
    'required',
    Rule::exists('units', 'id')->where('company_id', $companyId),
],

'brand_id'=>[
    'nullable',
    Rule::exists('brands', 'id')->where('company_id', $companyId),
],

'cost_price' =>
ValidationService::requiredAmount(),

'retail_price' =>
ValidationService::requiredAmount(),

'wholesale_price' =>
ValidationService::amount(),

'stock_alert' =>
ValidationService::quantity(),

'batch_no' =>
ValidationService::string(100),

'manufacture_date' =>
ValidationService::date(),

'expiry_date' =>
ValidationService::date(),

'allow_online' =>
ValidationService::boolean(),

'image' =>
ValidationService::image(),

]);


$data=[

'name'=>

$request->name,

'category_id'=>

$request->category_id,

'brand_id'=>

$request->brand_id,

'unit_id'=>

$request->unit_id,

'barcode' => $request->barcode,

'batch_no' => $request->batch_no,

'manufacture_date' => $request->manufacture_date,

'expiry_date' => $request->expiry_date,

'allow_online' => $request->boolean('allow_online'),

'cost_price'=>

$request->cost_price,

'retail_price'=>

$request->retail_price,

'wholesale_price'=>
$request->wholesale_price
?? $request->retail_price,

'stock_alert'=>

$request->stock_alert ?? 0,

'status'=>

$request->status,

'description'=>

$request->description,

];


/* IMAGE UPDATE */

$data['image'] =
    FileUploadService::replaceImage(
        $request,
        'image',
        $product->image,
        $this->productImageFolder(),
        800
    );

/* UPDATE */

$product->update(
$data
);


return redirect()

->route(

'company.products.index'

)

->with(

'success',

'Product Updated Successfully'

);

}

// 🔥 PRODUCT PROFILE

public function show($id)
{
    $product = Product::where(
        'company_id',
        auth()->user()->company_id
    )
    ->with(['unit', 'category', 'brand'])
    ->findOrFail($id);

    return view(
        'company.products.show',
        compact('product')
    );
}

/* =====================

PRODUCT PROFILE PRINT

===================== */

public function printProfile($id)
{
    $product = Product::where(
        'company_id',
        auth()->user()->company_id
    )
    ->with(['unit', 'category', 'brand'])
    ->findOrFail($id);

    $print = true;

    return view(
        'company.products.show',
        compact('product', 'print')
    );
}

/* =====================

PRODUCT LIST PRINT

===================== */

public function print(Request $request)
{

    $products = $this->filteredProductQuery($request)
        ->with(['brand'])
        ->latest()
        ->get();

    $totalProducts = $products->count();

    $totalStockQuantity = $products->sum('current_stock');

    $totalOutOfStock = $products->where('current_stock', '<=', 0)->count();

    return view(

        'company.products.print',

        compact(
            'products',
            'totalProducts',
            'totalStockQuantity',
            'totalOutOfStock'
        )

    );
}

public function destroy($id)
{

$product = Product::

where(
'id',
$id
)

->where(
'company_id',
auth()->user()->company_id
)

->firstOrFail();


DB::beginTransaction();

try{

/* IF PRODUCT USED */

if(

StockMovement::where(
'company_id',
auth()->user()->company_id
)
->where(
'product_id',
$product->id
)
->exists()

){

/* DELETE FILE FIRST */

FileUploadService::deleteFile(
    $product->image
);

/* ARCHIVE PRODUCT */

$product->update([

'status'=>'inactive',

'image'=>null

]);

$message =

'Product Archived Successfully';

}


/* HARD DELETE */

else{

/* DELETE IMAGE */

FileUploadService::deleteFile(
    $product->image
);



$product->delete();

$message =

'Product Deleted Successfully';

}

DB::commit();

return back()->with(

'success',

$message

);

}

catch(\Exception $e){

DB::rollBack();

throw $e;

}

}
    

    // 📥 EXPORT EXCEL

   public function exportExcel(Request $request)
{
    return Excel::download(

        new ProductsExport(

            auth()->user()->company_id,

            $request->search,

            $request->stock_filter,

            $request->brand_id

        ),

        'products.xlsx'
    );
}

    // 📄 EXPORT PDF

    public function exportPdf(Request $request)
{
    $products = $this->filteredProductQuery($request)
        ->with(['brand'])
        ->latest()
        ->get();

    $pdf = Pdf::loadView(
        'company.products.pdf',
        compact('products')
    );

    return $pdf->download(
        'products.pdf'
    );
}
}
