<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{

/**
 * INDEX
 */

public function index(Request $request)
{

$companyId =
auth()->user()->company_id;

$query =
ExpenseCategory::where(
'company_id',
$companyId
);

if($request->search){

$query->where(

'name',

'like',

'%'.$request->search.'%'

);

}

$categories =

$query

->latest()

->paginate(20)

->withQueryString();

return view(

'company.expense-category.index',

compact(

'categories'

)

);

}


/**
 * CREATE
 */

public function create()
{

return view(

'company.expense-category.create'

);

}


/**
 * STORE
 */

public function store(Request $request)
{

$request->validate([

'name'=>

'required|max:100',

'description'=>

'nullable'

]);

ExpenseCategory::create([

'company_id'=>

auth()->user()->company_id,

'name'=>

$request->name,

'description'=>

$request->description,

'status'=>1

]);

return redirect()

->route(

'company.expense-category.index'

)

->with(

'success',

'Expense category created successfully.'

);

}

}