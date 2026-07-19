<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Services\ValidationService;

class UnitController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FILTERED QUERY (shared by index() and print())
    |--------------------------------------------------------------------------
    */

    private function filteredUnitQuery(Request $request)
    {
        $query = Unit::where(
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
                    'short_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | UNIT LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $totalUnits = $this->filteredUnitQuery($request)->count();

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

        $units = $this->filteredUnitQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'company.units.index',
            compact('units', 'totalUnits', 'perPage')
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

        $request->validate([

            'name' => [

                'required',
                'max:100',

                ValidationService::uniquePerCompany(
                    'units',
                    'name',
                    $companyId
                ),

            ],

            'short_name' => [

                'required',
                'max:20',

                ValidationService::uniquePerCompany(
                    'units',
                    'short_name',
                    $companyId
                ),

            ],

        ]);

        Unit::create([

            'company_id' => $companyId,

            'name' => trim(
                $request->name
            ),

            'short_name' => trim(
                $request->short_name
            ),

        ]);

        return back()->with(
            'success',
            'Unit Added Successfully'
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

        $unit = Unit::where(
            'company_id',
            $companyId
        )
        ->findOrFail($id);

        $request->validate([

            'name' => [

                'required',
                'max:100',

                ValidationService::uniquePerCompany(
                    'units',
                    'name',
                    $companyId,
                    $unit->id
                ),

            ],

            'short_name' => [

                'required',
                'max:20',

                ValidationService::uniquePerCompany(
                    'units',
                    'short_name',
                    $companyId,
                    $unit->id
                ),

            ],

        ]);

        $unit->update([

            'name' => trim(
                $request->name
            ),

            'short_name' => trim(
                $request->short_name
            ),

        ]);

        return back()->with(
            'success',
            'Unit Updated Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE (PROTECTED)
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $unit = Unit::where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail($id);

        if (
            $unit
                ->products()
                ->exists()
        ) {

            return back()->with(
                'error',
                'Unit cannot be deleted because it is already used by one or more Products.'
            );
        }

        $unit->delete();

        return back()->with(
            'success',
            'Unit Deleted Successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UNIT LIST PRINT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $units = $this->filteredUnitQuery($request)
            ->latest()
            ->get();

        $totalUnits = $units->count();

        return view(
            'company.units.print',
            compact('units', 'totalUnits')
        );
    }
}
