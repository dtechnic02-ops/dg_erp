<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialYearController extends Controller
{

    public function index()
    {

        $financialYears = FinancialYear::where(
            'company_id',
            Auth::user()->company_id
        )
        ->latest()
        ->paginate(20);

        return view(
            'company.financial_years.index',
            compact('financialYears')
        );

    }



    public function create()
    {

        return view(
            'company.financial_years.form',
            [
                'financialYear'=>null
            ]
        );

    }



    public function store(Request $request)
    {

        $request->validate([

            'name'=>'required|max:255',

            'start_date'=>'required|date',

            'end_date'=>'required|date|after:start_date'

        ]);


        $exists = FinancialYear::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where(
            'name',
            $request->name
        )
        ->exists();


        if($exists){

            return back()
            ->withInput()
            ->with(
                'error',
                'Financial year already exists.'
            );

        }



        if($request->boolean('is_active')){

            FinancialYear::where(
                'company_id',
                Auth::user()->company_id
            )->update([

                'is_active'=>0

            ]);

        }



        FinancialYear::create([

            'company_id'=>Auth::user()->company_id,

            'name'=>$request->name,

            'start_date'=>$request->start_date,

            'end_date'=>$request->end_date,

            'is_active'=>$request->boolean(
                'is_active'
            ),

            'created_by'=>Auth::id()

        ]);


        return redirect()

        ->route(
            'company.financial-years.index'
        )

        ->with(

            'success',

            'Financial Year Created.'

        );

    }




    public function edit($id)
    {

        $financialYear = FinancialYear::where([

            'id'=>$id,

            'company_id'=>Auth::user()->company_id

        ])

        ->firstOrFail();


        return view(

            'company.financial_years.form',

            compact('financialYear')

        );

    }




    public function update(
        Request $request,
        $id
    )
    {

        $financialYear = FinancialYear::where([

            'id'=>$id,

            'company_id'=>Auth::user()->company_id

        ])

        ->firstOrFail();



        $request->validate([

            'name'=>'required|max:255',

            'start_date'=>'required|date',

            'end_date'=>'required|date|after:start_date'

        ]);



        $exists = FinancialYear::where(

            'company_id',
            Auth::user()->company_id

        )

        ->where(
            'name',
            $request->name
        )

        ->where(
            'id',
            '!=',
            $id
        )

        ->exists();



        if($exists){

            return back()

            ->withInput()

            ->with(

                'error',

                'Financial year already exists.'

            );

        }



        if($request->boolean('is_active')){

            FinancialYear::where(
                'company_id',
                Auth::user()->company_id
            )

            ->update([

                'is_active'=>0

            ]);

        }



        $financialYear->update([

            'name'=>$request->name,

            'start_date'=>$request->start_date,

            'end_date'=>$request->end_date,

            'is_active'=>$request->boolean(
                'is_active'
            )

        ]);


        return redirect()

        ->route(
            'company.financial-years.index'
        )

        ->with(

            'success',

            'Financial Year Updated.'

        );

    }




    public function destroy($id)
    {

        $financialYear = FinancialYear::where([

            'id'=>$id,

            'company_id'=>Auth::user()->company_id

        ])

        ->firstOrFail();



        if($financialYear->is_active){

            return back()->with(

                'error',

                'Active Financial Year Cannot Be Deleted.'

            );

        }



        $financialYear->delete();



        return back()->with(

            'success',

            'Financial Year Deleted.'

        );

    }

}