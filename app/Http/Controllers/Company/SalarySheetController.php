<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SalarySheet;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use Illuminate\Validation\Rule;
use App\Services\ValidationService;
use Illuminate\Support\Facades\DB;
class SalarySheetController extends Controller
{
   public function index(Request $request)
{
    $query = SalarySheet::with('employee')

        ->where(
            'company_id',
            auth()->user()->company_id
        );

    if($request->search){

        $query->whereHas(
            'employee',
            function($q) use ($request){

                $q->where(
    'first_name',
    'like',
    '%'.$request->search.'%'
)
->orWhere(
    'employee_code',
    'like',
    '%'.$request->search.'%'
)
->orWhere(
    'phone',
    'like',
    '%'.$request->search.'%'
);

            }
        );
    }
    if($request->salary_month){

    $query->where(
        'salary_month',
        $request->salary_month
    );
}

if($request->status){

    $query->where(
        'status',
        $request->status
    );
}

    $salarySheets = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    return view(
        'company.salary-sheets.index',
        compact('salarySheets')
    );
}
public function create()
{
    $employees = EmployeeAccount::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'status',
        1
    )
    ->orderBy('first_name')
    ->get();

    $financialYears = FinancialYear::where(
        'company_id',
        auth()->user()->company_id
    )
    ->orderByDesc('id')
    ->get();

    return view(
        'company.salary-sheets.create',
        compact(
            'employees',
            'financialYears'
        )
    );
}


public function store(Request $request)
{
    $companyId =
    auth()->user()->company_id;

    $financialYearId =
    $request->financial_year_id;

    $request->validate([

        'financial_year_id' =>
        'required',

        'employee_id' =>
        'required',

        'salary_month' => [

            'required',

            Rule::unique('salary_sheets')
                ->where(function ($query) use ($request) {

                    return $query
                        ->where(
                            'company_id',
                            auth()->user()->company_id
                        )
                        ->where(
                            'financial_year_id',
                            $request->financial_year_id
                        )
                        ->where(
                            'employee_id',
                            $request->employee_id
                        );

                })

        ],

        'working_days' =>
        'required|integer|min:1',

        'present_days' =>
        'required|integer|min:0',

        'absent_days' =>
        'nullable|integer|min:0',

        'allowance' =>
        'nullable|numeric',

        'bonus' =>
        'nullable|numeric',

        'overtime_amount' =>
        'nullable|numeric',

        'deduction' =>
        'nullable|numeric',

    ]);

    $employee =
    EmployeeAccount::where(
        'company_id',
        $companyId
    )
    ->findOrFail(
        $request->employee_id
    );

    if(
        $request->present_days
        +
        ($request->absent_days ?? 0)
        >
        $request->working_days
    ){

        return back()
            ->withInput()
            ->withErrors([
                'present_days' =>
                'Present + Absent days cannot exceed Working Days.'
            ]);

    }

    if($employee->basic_salary <= 0){

        return back()
            ->withInput()
            ->withErrors([
                'employee_id' =>
                'Employee basic salary is not set.'
            ]);

    }

    DB::transaction(function() use (

        $request,
        $companyId,
        $financialYearId,
        $employee

    ){

        $perDaySalary =

            $request->working_days > 0

            ?

            (
                $employee->basic_salary
                /
                $request->working_days
            )

            :

            0;

        $earnedSalary =

            $perDaySalary
            *
            $request->present_days;

        $netSalary = round(

            $earnedSalary
            +
            ($request->allowance ?? 0)
            +
            ($request->bonus ?? 0)
            +
            ($request->overtime_amount ?? 0)
            -
            ($request->deduction ?? 0),

            2

        );

        SalarySheet::create([

            'company_id' =>
            $companyId,

            'financial_year_id' =>
            $financialYearId,

            'employee_id' =>
            $employee->id,

            'salary_month' =>
            $request->salary_month,

            'basic_salary' =>
            $employee->basic_salary,

            'working_days' =>
            $request->working_days,

            'present_days' =>
            $request->present_days,

            'absent_days' =>
            $request->absent_days ?? 0,

            'allowance' =>
            $request->allowance ?? 0,

            'bonus' =>
            $request->bonus ?? 0,

            'overtime_amount' =>
            $request->overtime_amount ?? 0,

            'deduction' =>
            $request->deduction ?? 0,

            'net_salary' =>
            $netSalary,

            'status' =>
            'unpaid',

            'note' =>
            $request->note,

            'created_by' =>
            auth()->id()

        ]);

    });

    return redirect()
        ->route(
            'company.salary-sheets.index'
        )
        ->with(
            'success',
            'Salary Sheet Created Successfully'
        );
}






public function edit($id)
{
    $salarySheet = SalarySheet::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    $employees = EmployeeAccount::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'status',
        1
    )
    ->orderBy('first_name')
    ->get();

    return view(
        'company.salary-sheets.edit',
        compact(
            'salarySheet',
            'employees'
        )
    );
}
public function update(
    Request $request,
    $id
)
{
    $salarySheet = SalarySheet::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    $request->validate([

        'employee_id' => 'required',

        'salary_month' => [
            'required',

           Rule::unique('salary_sheets')
    ->ignore($salarySheet->id)
    ->where(function ($query) use ($request) {

        return $query
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->where(
                'financial_year_id',
                $request->financial_year_id
            )
            ->where(
                'employee_id',
                $request->employee_id
            );

    })
(function ($query) use ($request) {

    return $query
        ->where(
            'company_id',
            auth()->user()->company_id
        )
        ->where(
            'financial_year_id',
            $request->financial_year_id
        )
        ->where(
            'employee_id',
            $request->employee_id
        );

})
        ],

        'working_days' =>
        'required|integer|min:1',

        'present_days' =>
        'required|integer|min:0',

        'absent_days' =>
        'nullable|integer|min:0',

        'allowance' =>
        ValidationService::amount(),

        'bonus' =>
        ValidationService::amount(),

        'overtime_amount' =>
        ValidationService::amount(),

        'deduction' =>
        ValidationService::amount(),

    ]);

    $employee = EmployeeAccount::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail(
        $request->employee_id
    );

    if(
        $request->present_days +
        ($request->absent_days ?? 0)
        >
        $request->working_days
    ){
        return back()
            ->withInput()
            ->withErrors([
                'present_days' =>
                'Present + Absent days cannot exceed Working Days.'
            ]);
    }

    $perDaySalary =
        $employee->basic_salary /
        $request->working_days;

    $earnedSalary =
        $perDaySalary *
        $request->present_days;

    $netSalary = round(

        $earnedSalary
        +
        ($request->allowance ?? 0)
        +
        ($request->bonus ?? 0)
        +
        ($request->overtime_amount ?? 0)
        -
        ($request->deduction ?? 0),

        2

    );

    $salarySheet->update([

        'employee_id' =>
        $employee->id,

        'salary_month' =>
        $request->salary_month,

        'basic_salary' =>
        $employee->basic_salary,

        'working_days' =>
        $request->working_days,

        'present_days' =>
        $request->present_days,

        'absent_days' =>
        $request->absent_days ?? 0,

        'allowance' =>
        $request->allowance ?? 0,

        'bonus' =>
        $request->bonus ?? 0,

        'overtime_amount' =>
        $request->overtime_amount ?? 0,

        'deduction' =>
        $request->deduction ?? 0,

        'net_salary' =>
        $netSalary,

        'note' =>
        $request->note,

    ]);

    return redirect()
        ->route(
            'company.salary-sheets.index'
        )
        ->with(
            'success',
            'Salary Sheet Updated Successfully'
        );
}

public function destroy($id)
{
   
    $salarySheet = SalarySheet::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    $salarySheet->delete();

    return back()->with(
        'success',
        'Salary Sheet Deleted Successfully'
    );
}




public function show($id)
{
    $salarySheet = SalarySheet::with('employee')

        ->where(
            'company_id',
            auth()->user()->company_id
        )

        ->findOrFail($id);

    return view(
        'company.salary-sheets.show',
        compact('salarySheet')
    );
}
public function print(Request $request)
{
    $query = SalarySheet::with('employee')

        ->where(
            'company_id',
            auth()->user()->company_id
        );

    if($request->employee_id){

        $query->where(
            'employee_id',
            $request->employee_id
        );
    }

    if($request->salary_month){

        $query->where(
            'salary_month',
            $request->salary_month
        );
    }

    if($request->status){

        $query->where(
            'status',
            $request->status
        );
    }

    $salarySheets = $query
        ->orderBy(
            'salary_month',
            'desc'
        )
        ->get();

    return view(
        'company.salary-sheets.print',
        compact('salarySheets')
    );
}

}
