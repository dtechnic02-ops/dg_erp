<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    use AuthorizesCompanyPermission;

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_expense_categories');

        $companyId = auth()->user()->company_id;

        $query = ExpenseCategory::where('company_id', $companyId);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && in_array((int) $request->status, [0, 1], true)) {
            $query->where('status', (int) $request->status);
        }

        $categories = $query->latest()->paginate(20)->withQueryString();

        return view('company.expense-category.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        return view('company.expense-category.create');
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $request->validate([
            'name'        => ValidationService::requiredString(100),
            'description' => ValidationService::text(),
            'status'      => 'nullable|in:0,1',
        ]);

        ExpenseCategory::create([
            'company_id'  => auth()->user()->company_id,
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => $request->input('status', ExpenseCategory::STATUS_ACTIVE),
            'created_by'  => auth()->id(),
        ]);

        return redirect()
            ->route('company.expense-category.index')
            ->with('success', 'Expense category created successfully.');
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $category = ExpenseCategory::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.expense-category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $request->validate([
            'name'        => ValidationService::requiredString(100),
            'description' => ValidationService::text(),
            'status'      => 'required|in:0,1',
        ]);

        $category = ExpenseCategory::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $category->update([
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => (int) $request->status,
        ]);

        return redirect()
            ->route('company.expense-category.index')
            ->with('success', 'Expense category updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $category = ExpenseCategory::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $inUse = Expense::where('company_id', auth()->user()->company_id)
            ->where('expense_category_id', $category->id)
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete category because it is used by one or more expense entries.');
        }

        $category->delete();

        return back()->with('success', 'Expense category deleted successfully.');
    }
}
