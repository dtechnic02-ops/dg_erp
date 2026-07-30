<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\ChartAccount;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncomeCategoryController extends Controller
{
    use AuthorizesCompanyPermission;

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_income_categories');

        $companyId = auth()->user()->company_id;

        $query = IncomeCategory::where('company_id', $companyId);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && in_array((int) $request->status, [0, 1], true)) {
            $query->where('status', (int) $request->status);
        }

        $categories = $query->latest()->paginate(20)->withQueryString();

        return view('company.income-category.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('manage_income_categories');

        $chartAccounts = $this->incomeChartAccounts(auth()->user()->company_id);

        return view('company.income-category.create', compact('chartAccounts'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('manage_income_categories');

        $request->validate([
            'chart_account_id' => 'required|integer',
            'name'   => ValidationService::requiredString(100),
            'code'   => ValidationService::text(50),
            'note'   => ValidationService::text(),
            'status' => 'nullable|in:0,1',
        ]);

        $companyId = auth()->user()->company_id;
        $this->validateIncomeChartAccount($companyId, (int) $request->chart_account_id);

        IncomeCategory::create([
            'company_id' => $companyId,
            'chart_account_id' => $request->chart_account_id,
            'name'       => $request->name,
            'code'       => $request->code,
            'note'       => $request->note,
            'status'     => $request->input('status', IncomeCategory::STATUS_ACTIVE),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('company.income-category.index')
            ->with('success', 'Income category created successfully.');
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('manage_income_categories');

        $category = IncomeCategory::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $chartAccounts = $this->incomeChartAccounts(auth()->user()->company_id);

        return view('company.income-category.edit', compact('category', 'chartAccounts'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('manage_income_categories');

        $request->validate([
            'chart_account_id' => 'required|integer',
            'name'   => ValidationService::requiredString(100),
            'code'   => ValidationService::text(50),
            'note'   => ValidationService::text(),
            'status' => 'required|in:0,1',
        ]);

        $companyId = auth()->user()->company_id;

        $category = IncomeCategory::where('company_id', $companyId)
            ->findOrFail($id);

        $this->validateIncomeChartAccount($companyId, (int) $request->chart_account_id);

        $category->update([
            'chart_account_id' => $request->chart_account_id,
            'name'   => $request->name,
            'code'   => $request->code,
            'note'   => $request->note,
            'status' => (int) $request->status,
        ]);

        return redirect()
            ->route('company.income-category.index')
            ->with('success', 'Income category updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeCompanyPermission('manage_income_categories');

        $category = IncomeCategory::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $inUse = Income::where('company_id', auth()->user()->company_id)
            ->where('income_category_id', $category->id)
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete category because it is used by one or more income entries.');
        }

        $category->delete();

        return back()->with('success', 'Income category deleted successfully.');
    }

    private function incomeChartAccounts(int $companyId)
    {
        return ChartAccount::query()
            ->forCompany($companyId)
            ->active()
            ->ofClass('income')
            ->orderBy('code')
            ->get();
    }

    private function validateIncomeChartAccount(int $companyId, int $chartAccountId): ChartAccount
    {
        $chartAccount = ChartAccount::query()
            ->forCompany($companyId)
            ->active()
            ->ofClass('income')
            ->find($chartAccountId);

        if ($chartAccount === null) {
            throw ValidationException::withMessages([
                'chart_account_id' => 'Select an active income chart account for this category.',
            ]);
        }

        return $chartAccount;
    }
}
