<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends Controller
{

    public function index()
    {

        $units = Unit::

        where(
            'company_id',
            auth()->user()->company_id
        )

        ->latest()

        ->paginate(20);

        return view(
            'company.units.index',
            compact('units')
        );

    }



    public function store(Request $request)
    {

        $request->validate([

            'name'=>

            'required|max:100|unique:units,name,NULL,id,company_id,'.auth()->user()->company_id,

            'short_name'=>

            'required|max:20|unique:units,short_name,NULL,id,company_id,'.auth()->user()->company_id,

        ]);


        Unit::create([

            'company_id'=>

            auth()->user()->company_id,

            'name'=>

            trim(
                $request->name
            ),

            'short_name'=>

            trim(
                $request->short_name
            ),

        ]);


        return back()

        ->with(
            'success',
            'Unit Added Successfully'
        );

    }



    public function edit($id)
    {

        $unit = Unit::

        where(
            'company_id',
            auth()->user()->company_id
        )

        ->findOrFail($id);

        return response()->json($unit);

    }



    public function update(Request $request,$id)
    {

        $unit = Unit::

        where(
            'company_id',
            auth()->user()->company_id
        )

        ->findOrFail($id);


        $request->validate([

            'name'=>

            'required|max:100|unique:units,name,'.$unit->id.',id,company_id,'.auth()->user()->company_id,

            'short_name'=>

            'required|max:20|unique:units,short_name,'.$unit->id.',id,company_id,'.auth()->user()->company_id,

        ]);


        $unit->update([

            'name'=>

            trim(
                $request->name
            ),

            'short_name'=>

            trim(
                $request->short_name
            ),

        ]);


        return back()

        ->with(
            'success',
            'Unit Updated Successfully'
        );

    }



    public function destroy($id)
    {

        $unit = Unit::

        where(
            'company_id',
            auth()->user()->company_id
        )

        ->findOrFail($id);


        $unit->delete();


        return back()

        ->with(
            'success',
            'Unit Deleted Successfully'
        );

    }

}

