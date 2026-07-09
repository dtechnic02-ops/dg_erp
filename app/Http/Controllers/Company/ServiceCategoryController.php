<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * INDEX
     */
    public function index(Request $request)
    {
        $query = ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        );

        // 🔍 SEARCH
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');

            });
        }

        // STATUS FILTER
        if ($request->status) {

            $query->where('status', $request->status);
        }

        $categories = $query
            ->latest()
            ->paginate(15);

        return view(
            'company.service-categories.index',
            compact('categories')
        );
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required|max:255',

        ]);

        $uploadPath = null;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = public_path(
                'companies/' .
                auth()->user()->company_id .
                '/service-categories'
            );

            if (!file_exists($path)) {

                mkdir($path, 0777, true);
            }

            $file->move($path, $filename);

            $uploadPath =
                'companies/' .
                auth()->user()->company_id .
                '/service-categories/' .
                $filename;
        }

        ServiceCategory::create([

            'company_id' => auth()->user()->company_id,

            'name' => $request->name,

        'slug' => Str::slug($request->name),

           'upload_path' => $uploadPath,

            'status' => $request->status ?? 'active',

            'created_by' => auth()->id(),

        ]);

        return back()->with(
            'success',
            'Service category created successfully.'
        );
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $category = ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        )->findOrFail($id);

        $request->validate([

            'name' => 'required|max:255',

        ]);

        $uploadPath = $category->upload_path;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {

    // DELETE OLD IMAGE
    if ($category->upload_path) {

    $imagePath = public_path($category->upload_path);

    if (file_exists($imagePath)) {

        unlink($imagePath);
    }
   }

    // NEW IMAGE
    $file = $request->file('image');

    $filename =
        time() . '_' .
        $file->getClientOriginalName();

    $path = public_path(
        'companies/' .
        auth()->user()->company_id .
        '/service-categories'
    );

    if (!file_exists($path)) {

        mkdir($path, 0777, true);
    }

    $file->move($path, $filename);

    $uploadPath =
        'companies/' .
        auth()->user()->company_id .
        '/service-categories/' .
        $filename;
   }
        $category->update([

            'name' => $request->name,

           'slug' => Str::slug($request->name),

            'upload_path' => $uploadPath,

            'status' => $request->status ?? 'active',

        ]);

        return back()->with(
            'success',
            'Service category updated successfully.'
        );
    }

    /**
     * DELETE
     */
   public function destroy($id)
    {
    $category = ServiceCategory::where(
        'company_id',
        auth()->user()->company_id
    )->findOrFail($id);

    // DELETE IMAGE
 if (
    $category->upload_path &&
    file_exists(public_path($category->upload_path))
) {

    unlink(public_path($category->upload_path));
}

    // DELETE DATABASE ROW
    $category->delete();

    return back()->with(
        'success',
        'Service category deleted successfully.'
    );
      }

    /**
     * PRINT VIEW
     */
    public function print(Request $request)
    {
        $query = ServiceCategory::where(
            'company_id',
            auth()->user()->company_id
        );

        // SEARCH
        if ($request->search) {

            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query
            ->latest()
            ->get();

        return view(
            'company.service-categories.print',
            compact('categories')
        );
    }
}