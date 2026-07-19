<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductCategory;
use App\Services\ValidationService;

class ProductCategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FILTERED QUERY (shared by index() and print())
    |--------------------------------------------------------------------------
    */

    private function filteredCategoryQuery(Request $request)
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

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $totalCategories = $this->filteredCategoryQuery($request)->count();

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

        $categories = $this->filteredCategoryQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'company.categories.index',
            compact('categories', 'totalCategories', 'perPage')
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

                ValidationService::uniquePerCompany(
                    'product_categories',
                    'name',
                    $companyId
                ),

            ],

            'description' => ValidationService::text(1000),

            'status' => ValidationService::enum(['active', 'inactive']),

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
$validated['status'] ?? ProductCategory::STATUS_ACTIVE

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

                ValidationService::uniquePerCompany(
                    'product_categories',
                    'name',
                    $companyId,
                    $id
                ),

            ],

            'description' => ValidationService::text(1000),

            'status' => ValidationService::enum(['active', 'inactive']),

        ]);

        $category = ProductCategory::company()
            ->findOrFail($id);

        $category->update([

            'name' => trim(
                $validated['name']
            ),

            'description' => trim(
                $validated['description'] ?? ''
            ),

            'status' => $validated['status'] ?? $category->status,

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
        $category = ProductCategory::company()
            ->findOrFail($id);

        if (
            $category
                ->products()
                ->exists()
        ) {

            return back()->with(
                'error',
                'Category cannot be deleted because it is already used by one or more Products.'
            );
        }

        DB::transaction(function () use ($category) {

            $category->delete();
        });

        return back()->with(
            'success',
            'Category Deleted Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY LIST PRINT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $categories = $this->filteredCategoryQuery($request)
            ->latest()
            ->get();

        $totalCategories = $categories->count();

        return view(
            'company.categories.print',
            compact('categories', 'totalCategories')
        );
    }
}
