<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\FileUploadService;
use App\Services\ValidationService;

class ServiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FILTERED QUERY (shared by index() and print())
    |--------------------------------------------------------------------------
    */

    private function filteredServiceQuery(Request $request)
    {
        $query = Service::with(['category'])
            ->where(
                'company_id',
                auth()->user()->company_id
            );

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'service_code',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($request->filled('category_id')) {

            $query->where(
                'service_category_id',
                $request->category_id
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE IMAGE FOLDER
    |--------------------------------------------------------------------------
    */

    private function serviceImageFolder()
    {
        return 'companies/' .
            auth()->user()->company_id .
            '/services';
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY SERVICE CATEGORIES
    |--------------------------------------------------------------------------
    */

    private function companyServiceCategories()
    {
        return ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        )
        ->where('status', 'active')
        ->orderBy('name')
        ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $totalServices = $this->filteredServiceQuery($request)->count();

        $allowedPerPage = [10, 25, 50, 100, 200, 500];

        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage)) {

            $perPage = 10;

        }

        $services = $this->filteredServiceQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $categories = $this->companyServiceCategories();

        return view(
            'company.services.index',
            compact(
                'services',
                'totalServices',
                'perPage',
                'categories'
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
        $categories = $this->companyServiceCategories();

        return view(
            'company.services.form',
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

        Validator::make(
            $request->all(),
            [

                'name' => [

                    'required',
                    'max:255',

                    ValidationService::uniquePerCompany(
                        'services',
                        'name',
                        $companyId
                    ),

                ],

                'service_category_id' => [

                    'nullable',

                    Rule::exists('service_categories', 'id')->where(
                        fn ($q) => $q->where('company_id', $companyId)
                    ),

                ],

                'price' => ValidationService::requiredAmount(),

                'description' => ValidationService::text(1000),

                'status' => ValidationService::enum(['active', 'inactive']),

                'image' => ValidationService::image(),

            ]
        )->validate();

        Service::create([

            'company_id' => $companyId,

            'created_by' => auth()->id(),

            'name' => trim($request->name),

            'slug' => Str::slug($request->name),

            'service_category_id' => $request->service_category_id,

            'price' => $request->price,

            'description' => trim($request->description ?? ''),

            'status' => $request->status ?? 'active',

            'upload_path' => FileUploadService::replaceImage(
                $request,
                'image',
                null,
                $this->serviceImageFolder()
            ),

        ]);

        return redirect()
            ->route('company.services.index')
            ->with(
                'success',
                'Service Added Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $service = Service::with(['category', 'vat'])
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        return view(
            'company.services.show',
            compact('service')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $service = Service::where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

        $categories = $this->companyServiceCategories();

        return view(
            'company.services.form',
            compact('service', 'categories')
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

        $service = Service::where(
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
                        'services',
                        'name',
                        $companyId,
                        $service->id
                    ),

                ],

                'service_category_id' => [

                    'nullable',

                    Rule::exists('service_categories', 'id')->where(
                        fn ($q) => $q->where('company_id', $companyId)
                    ),

                ],

                'price' => ValidationService::requiredAmount(),

                'description' => ValidationService::text(1000),

                'status' => ValidationService::enum(['active', 'inactive']),

                'image' => ValidationService::image(),

            ]
        )->validate();

        $service->update([

            'name' => trim($request->name),

            'slug' => Str::slug($request->name),

            'service_category_id' => $request->service_category_id,

            'price' => $request->price,

            'description' => trim($request->description ?? ''),

            'status' => $request->status ?? $service->status,

            'upload_path' => FileUploadService::replaceImage(
                $request,
                'image',
                $service->upload_path,
                $this->serviceImageFolder()
            ),

        ]);

        return redirect()
            ->route('company.services.index')
            ->with(
                'success',
                'Service Updated Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE (PROTECTED)
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $service = Service::where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

        if (
            $service
                ->salesItems()
                ->exists()
        ) {

            return back()->with(
                'error',
                'Service cannot be deleted because it is already used by one or more Sales Invoices.'
            );
        }

        FileUploadService::deleteFile($service->upload_path);

        $service->delete();

        return back()->with(
            'success',
            'Service Deleted Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE LIST PRINT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $services = $this->filteredServiceQuery($request)
            ->latest()
            ->get();

        $totalServices = $services->count();

        return view(
            'company.services.print',
            compact('services', 'totalServices')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE PROFILE PRINT
    |--------------------------------------------------------------------------
    */

    public function printProfile($id)
    {
        $service = Service::with(['category', 'vat'])
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        $print = true;

        return view(
            'company.services.show',
            compact('service', 'print')
        );
    }
}
