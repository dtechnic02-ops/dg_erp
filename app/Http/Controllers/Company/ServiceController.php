<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Vat;

use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * INDEX
     */
    public function index(Request $request)
    {
        $query = Service::with([
                'category',
                'vat'
            ])
            ->where(
                'company_id',
                auth()->user()->company_id
            );

        // SEARCH
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'service_code',
                    'like',
                    '%' . $request->search . '%'
                );
            });
        }

        // CATEGORY FILTER
        if ($request->category_id) {

            $query->where(
                'service_category_id',
                $request->category_id
            );
        }

        // STATUS FILTER
        if ($request->status) {

            $query->where(
                'status',
                $request->status
            );
        }

        $services = $query
            ->latest()
            ->paginate(15);

        $categories = ServiceCategory::where(
                'company_id',
                auth()->user()->company_id
            )
            ->where('status', 'active')
            ->get();

        $vats = Vat::where(
                'company_id',
                auth()->user()->company_id
            )
            ->where('status', 'active')
            ->get();

        return view(
            'company.services.index',
            compact(
                'services',
                'categories',
                'vats'
            )
        );
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required|max:255',

            'price' => 'required|numeric|min:0',

        ]);

        $uploadPath = null;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename =
                time() .
                '_' .
                $file->getClientOriginalName();

            $path = public_path(
                'companies/' .
                auth()->user()->company_id .
                '/services'
            );

            if (!file_exists($path)) {

                mkdir($path, 0777, true);
            }

            $file->move($path, $filename);

            $uploadPath =
                'companies/' .
                auth()->user()->company_id .
                '/services/' .
                $filename;
        }

        Service::create([

            'company_id' =>
                auth()->user()->company_id,

            'service_category_id' =>
                $request->service_category_id,

            'name' =>
                $request->name,

            'service_code' =>
                $request->service_code,

            'slug' =>
                Str::slug($request->name),

            'price' =>
                $request->price,

            'vat_id' =>
                $request->vat_id,

            'upload_path' =>
                $uploadPath,

            'description' =>
                $request->description,

            'status' =>
                $request->status ?? 'active',

            'created_by' =>
                auth()->id(),

        ]);

        return back()->with(
            'success',
            'Service created successfully.'
        );
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $service = Service::where(
            'company_id',
            auth()->user()->company_id
        )->findOrFail($id);

        $request->validate([

            'name' => 'required|max:255',

            'price' => 'required|numeric|min:0',

        ]);

        $uploadPath = $service->upload_path;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {

            // DELETE OLD IMAGE
            if (
                $service->upload_path &&
                file_exists(public_path($service->upload_path))
            ) {

                unlink(public_path($service->upload_path));
            }

            $file = $request->file('image');

            $filename =
                time() .
                '_' .
                $file->getClientOriginalName();

            $path = public_path(
                'companies/' .
                auth()->user()->company_id .
                '/services'
            );

            if (!file_exists($path)) {

                mkdir($path, 0777, true);
            }

            $file->move($path, $filename);

            $uploadPath =
                'companies/' .
                auth()->user()->company_id .
                '/services/' .
                $filename;
        }

        $service->update([

            'service_category_id' =>
                $request->service_category_id,

            'name' =>
                $request->name,

            'service_code' =>
                $request->service_code,

            'slug' =>
                Str::slug($request->name),

            'price' =>
                $request->price,

            'vat_id' =>
                $request->vat_id,

            'upload_path' =>
                $uploadPath,

            'description' =>
                $request->description,

            'status' =>
                $request->status ?? 'active',

        ]);

        return back()->with(
            'success',
            'Service updated successfully.'
        );
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        $service = Service::where(
            'company_id',
            auth()->user()->company_id
        )->findOrFail($id);

        // DELETE IMAGE
        if (
            $service->upload_path &&
            file_exists(public_path($service->upload_path))
        ) {

            unlink(public_path($service->upload_path));
        }

        // DELETE DATABASE ROW
        $service->delete();

        return back()->with(
            'success',
            'Service deleted successfully.'
        );
    }
}
