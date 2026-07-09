<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductCategory;
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
// 🔥 PRODUCT LIST


public function index(Request $request)
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

    // 🔥 FINAL PRODUCTS

  $products = $query
   ->latest() 
   ->paginate(20) 
   ->withQueryString(); 
   return view( 'company.products.index', compact('products') );
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

        return view(
            'company.products.form',
            [
                'product' => null,
                'units' => $units,
                'categories' => $categories
            ]
        );
    }

    // 🔥 STORE PRODUCT
public function store(Request $request)
{

$request->validate([

'name' => [

    'required',

    'max:255',

    Rule::unique(
        'products',
        'name'
    )->where(

        fn ($q) =>

        $q->where(
            'company_id',
            auth()->user()->company_id
        )

    ),

],

'barcode' => [

    'nullable',

    'max:100',

    Rule::unique(
        'products',
        'barcode'
    )->where(
        fn ($q) =>
        $q->where(
            'company_id',
            auth()->user()->company_id
        )
    ),

],
'category_id'=>'required',

'unit_id'=>'required',

'cost_price' =>
ValidationService::requiredAmount(),

'retail_price' =>
ValidationService::requiredAmount(),

'wholesale_price' =>
ValidationService::amount(),

'stock_alert' =>
ValidationService::amount(),

'opening_stock' =>

ValidationService::amount(),

'image' =>
ValidationService::document(),

]);


$imagePath = null;

$folder =
'companies/'.
auth()->user()->company_id.
'/products';

if($request->hasFile('image')){

    $imagePath =
        FileUploadService::uploadImage(
            $request->file('image'),
            $folder,
            800
        );

}


/* CREATE PRODUCT */
$activeFinancialYear = FinancialYear::where(
    'company_id',
    auth()->user()->company_id
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

auth()->user()->company_id,
'name'=>

$request->name,

'category_id'=>

$request->category_id,

'unit_id'=>

$request->unit_id,

'barcode' => $request->barcode,

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


return view(

'company.products.form',

compact(

'product',

'units',

'categories'

)

);

}

    // 🔥 UPDATE PRODUCT

    public function update(
Request $request,
$id
){

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


$request->validate([

'name' => [

    'required',

    'max:255',

    Rule::unique(
        'products',
        'name'
    )

    ->ignore(
        $product->id
    )

    ->where(

        fn ($q) =>

        $q->where(
            'company_id',
            auth()->user()->company_id
        )

    ),

],
'barcode' => [

    'nullable',

    'string',

    'max:100',

    Rule::unique(
        'products',
        'barcode'
    )

    ->ignore(
        $product->id
    )

    ->where(

        fn ($q) =>

        $q->where(
            'company_id',
            auth()->user()->company_id
        )

    ),

],

'category_id'=>'required',

'unit_id'=>'required',

'cost_price' =>
ValidationService::requiredAmount(),

'retail_price' =>
ValidationService::requiredAmount(),

'wholesale_price' =>
ValidationService::amount(),

'stock_alert' =>
ValidationService::amount(),
'image' =>
ValidationService::document(),

]);


$data=[

'name'=>

$request->name,

'category_id'=>

$request->category_id,

'unit_id'=>

$request->unit_id,

'barcode' => $request->barcode,

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

'image'=>

$product->image

];





/* IMAGE UPDATE */

$folder =
'companies/'.
auth()->user()->company_id.
'/products';

$data['image'] =
    FileUploadService::replaceImage(
        $request,
        'image',
        $product->image,
        $folder,
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

            $request->stock_filter

        ),

        'products.xlsx'
    );
}

    // 📄 EXPORT PDF

    public function exportPdf(Request $request)
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

    // SEARCH

    if ($request->search)
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

    // STOCK FILTER

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

    $products = $query
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