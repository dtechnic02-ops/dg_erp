<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ChartAccount;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        $categories = $query->with('chartAccount')->latest()->paginate(20)->withQueryString();

        return view('company.expense-category.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $chartAccounts = $this->expenseChartAccounts(auth()->user()->company_id);

        return view('company.expense-category.create', compact('chartAccounts'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $request->validate([
            'chart_account_id' => 'required|integer',
            'name'        => ValidationService::requiredString(100),
            'description' => ValidationService::text(),
            'status'      => 'nullable|in:0,1',
        ]);

        $companyId = auth()->user()->company_id;
        $this->validateExpenseChartAccount($companyId, (int) $request->chart_account_id);

        ExpenseCategory::create([
            'company_id'  => $companyId,
            'chart_account_id' => $request->chart_account_id,
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

        $chartAccounts = $this->expenseChartAccounts(auth()->user()->company_id);

        return view('company.expense-category.edit', compact('category', 'chartAccounts'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('manage_expense_categories');

        $request->validate([
            'chart_account_id' => 'required|integer',
            'name'        => ValidationService::requiredString(100),
            'description' => ValidationService::text(),
            'status'      => 'required|in:0,1',
        ]);

        $companyId = auth()->user()->company_id;

        $category = ExpenseCategory::where('company_id', $companyId)
            ->findOrFail($id);

        $this->validateExpenseChartAccount($companyId, (int) $request->chart_account_id);

        $category->update([
            'chart_account_id' => $request->chart_account_id,
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

    private function expenseChartAccounts(int $companyId)
    {
        return ChartAccount::query()
            ->forCompany($companyId)
            ->active()
            ->ofClass('expense')
            ->orderBy('code')
            ->get();
    }

    private function validateExpenseChartAccount(int $companyId, int $chartAccountId): ChartAccount
    {
        $chartAccount = ChartAccount::query()
            ->forCompany($companyId)
            ->active()
            ->ofClass('expense')
            ->find($chartAccountId);

        if ($chartAccount === null) {
            throw ValidationException::withMessages([
                'chart_account_id' => 'Select an active expense chart account for this category.',
            ]);
        }

        return $chartAccount;
    }
}
