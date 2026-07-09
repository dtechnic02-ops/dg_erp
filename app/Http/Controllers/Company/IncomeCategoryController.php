<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\IncomeCategory;

class IncomeCategoryController extends Controller
{

public function index()
{

$categories=

IncomeCategory::where(

'company_id',

auth()->user()->company_id

)

->latest()

->paginate(20);

return view(

'company.income-category.index',

compact(

'categories'

)

);

}



public function create()
{

return view(

'company.income-category.create'

);

}



public function store(
Request $request
)
{

$request->validate([

'name'=>'required'

]);

IncomeCategory::create([

'company_id'=>

auth()->user()->company_id,

'name'=>

$request->name,

'code'=>

$request->code,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

return redirect()

->route(

'company.income-category.index'

)

->with(

'success',

'Category created.'

);

}



public function destroy($id)
{

IncomeCategory::where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id)

->delete();

return back()

->with(

'success',

'Deleted.'

);

}

}