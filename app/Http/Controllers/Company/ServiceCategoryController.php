<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\FileUploadService;
use App\Services\ValidationService;

class ServiceCategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FILTERED QUERY (shared by index() and print())
    |--------------------------------------------------------------------------
    */

    private function filteredServiceCategoryQuery(Request $request)
    {
        $query = ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        );

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
    | SERVICE CATEGORY IMAGE FOLDER
    |--------------------------------------------------------------------------
    */

    private function serviceCategoryImageFolder()
    {
        return 'companies/' .
            auth()->user()->company_id .
            '/service-categories';
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $totalServiceCategories = $this->filteredServiceCategoryQuery($request)->count();

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

        $categories = $this->filteredServiceCategoryQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'company.service-categories.index',
            compact('categories', 'totalServiceCategories', 'perPage')
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

        Validator::make(
            $request->all(),
            [

                'name' => [

                    'required',
                    'max:255',

                    ValidationService::uniquePerCompany(
                        'service_categories',
                        'name',
                        $companyId
                    ),

                ],

                'status' => ValidationService::enum(['active', 'inactive']),

                'image' => ValidationService::document(),

            ]
        )->validate();

        ServiceCategory::create([

            'company_id' => $companyId,

            'created_by' => auth()->id(),

            'name' => trim($request->name),

            'slug' => Str::slug($request->name),

            'status' => $request->status ?? 'active',

            'upload_path' => FileUploadService::replaceFile(
                $request,
                'image',
                null,
                $this->serviceCategoryImageFolder()
            ),

        ]);

        return back()->with(
            'success',
            'Service Category Added Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;

        $category = ServiceCategory::where(
            'company_id',
            $companyId
        )
        ->findOrFail($id);

        Validator::make(
            $request->all(),
            [

                'name' => [

                    'required',
                    'max:255',

                    ValidationService::uniquePerCompany(
                        'service_categories',
                        'name',
                        $companyId,
                        $category->id
                    ),

                ],

                'status' => ValidationService::enum(['active', 'inactive']),

                'image' => ValidationService::document(),

            ]
        )->validate();

        $category->update([

            'name' => trim($request->name),

            'slug' => Str::slug($request->name),

            'status' => $request->status ?? $category->status,

            'upload_path' => FileUploadService::replaceFile(
                $request,
                'image',
                $category->upload_path,
                $this->serviceCategoryImageFolder()
            ),

        ]);

        return back()->with(
            'success',
            'Service Category Updated Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE (PROTECTED)
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $category = ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

        if (
            $category
                ->services()
                ->exists()
        ) {

            return back()->with(
                'error',
                'Service Category cannot be deleted because it is already used by one or more Services.'
            );
        }

        FileUploadService::deleteFile($category->upload_path);

        $category->delete();

        return back()->with(
            'success',
            'Service Category Deleted Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE CATEGORY LIST PRINT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $categories = $this->filteredServiceCategoryQuery($request)
            ->latest()
            ->get();

        $totalServiceCategories = $categories->count();

        return view(
            'company.service-categories.print',
            compact('categories', 'totalServiceCategories')
        );
    }
}
