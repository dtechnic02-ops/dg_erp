<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CATEGORY LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = ProductCategory::company();

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );
        }

        $categories = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view(
            'company.categories.index',
            compact('categories')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([

            'name' => [

                'required',
                'max:255',

                'unique:product_categories,name,NULL,id,company_id,' .
                $companyId

            ],

            'description' => [

                'nullable',
                'max:1000'

            ]

        ]);

    ProductCategory::firstOrCreate(

[
'company_id'=>$companyId,

'name'=>trim(
$validated['name']
)
],

[
'description'=>trim(
$validated['description'] ?? ''
),

'status'=>
ProductCategory::STATUS_ACTIVE

]

);

        return back()->with(
            'success',
            'Category Added Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    )
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([

            'name' => [

                'required',
                'max:255',

                'unique:product_categories,name,' .
                $id .
                ',id,company_id,' .
                $companyId

            ],

            'description' => [

                'nullable',
                'max:1000'

            ]

        ]);

        $category = ProductCategory::company()
            ->findOrFail($id);

        $category->update([

            'name' => trim(
                $validated['name']
            ),

            'description' => trim(
                $validated['description'] ?? ''
            )

        ]);

        return back()->with(
            'success',
            'Category Updated Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $category = ProductCategory::company()
                ->findOrFail($id);

            if (
                method_exists(
                    $category,
                    'products'
                )
                &&
                $category
                    ->products()
                    ->exists()
            ) {

                abort(
                    422,
                    'Category is being used by products'
                );
            }

            $category->delete();
        });

        return back()->with(
            'success',
            'Category Deleted Successfully'
        );
    }
}

