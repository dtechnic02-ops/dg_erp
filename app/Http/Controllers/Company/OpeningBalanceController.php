<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpeningBalanceRequest;
use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\OpeningBalance;
use App\Models\Supplier;
use App\Services\OpeningBalanceService;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function __construct(private readonly OpeningBalanceService $service) {}

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $items = OpeningBalance::where('company_id', $companyId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->with('financialYear')->latest()->paginate(25)->withQueryString();
        return view('company.opening_balance.index', compact('items'));
    }

    public function create() { return view('company.opening_balance.create', $this->formData()); }

    public function store(OpeningBalanceRequest $request)
    {
        $opening = $this->service->create(auth()->user()->company_id, auth()->id(), $request->validated());
        return redirect()->route('company.opening-balances.show', $opening)->with('success', 'Opening Balance draft created.');
    }

    public function show(OpeningBalance $openingBalance)
    {
        $openingBalance = $this->companyRecord($openingBalance)->load(['lines.chartAccount', 'lines.operationalAccount', 'financialYear', 'journal.items', 'accountingEntry.lines']);
        return view('company.opening_balance.show', compact('openingBalance'));
    }

    public function edit(OpeningBalance $openingBalance)
    {
        $openingBalance = $this->companyRecord($openingBalance)->load('lines');
        abort_unless($openingBalance->status === OpeningBalance::STATUS_DRAFT && ! $openingBalance->is_locked, 409);
        return view('company.opening_balance.edit', $this->formData() + compact('openingBalance'));
    }

    public function update(OpeningBalanceRequest $request, OpeningBalance $openingBalance)
    {
        $opening = $this->service->update($this->companyRecord($openingBalance), auth()->user()->company_id, auth()->id(), $request->validated());
        return redirect()->route('company.opening-balances.show', $opening)->with('success', 'Opening Balance draft updated.');
    }

    public function submit(OpeningBalance $openingBalance) { return $this->action($this->service->submit($openingBalance, auth()->user()->company_id, auth()->id()), 'submitted'); }
    public function approve(OpeningBalance $openingBalance) { return $this->action($this->service->approve($openingBalance, auth()->user()->company_id, auth()->id()), 'approved'); }
    public function post(OpeningBalance $openingBalance) { return $this->action($this->service->post($openingBalance, auth()->user()->company_id, auth()->id()), 'posted'); }

    public function cancel(Request $request, OpeningBalance $openingBalance)
    {
        $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        return $this->action($this->service->cancel($openingBalance, auth()->user()->company_id, auth()->id(), $request->reason), 'cancelled');
    }

    public function reverse(Request $request, OpeningBalance $openingBalance)
    {
        $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        return $this->action($this->service->reverse($openingBalance, auth()->user()->company_id, auth()->id(), $request->reason), 'reversed');
    }

    public function lock(Request $request, OpeningBalance $openingBalance) { return $this->lockAction($request, $openingBalance, true); }
    public function unlock(Request $request, OpeningBalance $openingBalance) { return $this->lockAction($request, $openingBalance, false); }

    public function audit(OpeningBalance $openingBalance)
    {
        $openingBalance = $this->companyRecord($openingBalance)->load(['audits.user']);
        return view('company.opening_balance.audit', compact('openingBalance'));
    }

    public function print(OpeningBalance $openingBalance)
    {
        $openingBalance = $this->companyRecord($openingBalance)->load(['lines.chartAccount', 'financialYear', 'journal', 'accountingEntry']);
        return view('company.opening_balance.print', compact('openingBalance'));
    }

    private function lockAction(Request $request, OpeningBalance $openingBalance, bool $locked)
    {
        $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        return $this->action($this->service->setLock($openingBalance, auth()->user()->company_id, auth()->id(), $locked, $request->reason), $locked ? 'locked' : 'unlocked');
    }

    private function action(OpeningBalance $opening, string $action)
    {
        return redirect()->route('company.opening-balances.show', $opening)->with('success', "Opening Balance {$action}.");
    }

    private function companyRecord(OpeningBalance $opening): OpeningBalance
    {
        abort_unless((int) $opening->company_id === (int) auth()->user()->company_id, 404);
        return $opening;
    }

    private function formData(): array
    {
        $companyId = auth()->user()->company_id;
        return [
            'financialYears' => FinancialYear::where('company_id', $companyId)->where('is_active', 1)->orderByDesc('start_date')->get(),
            'chartAccounts' => ChartAccount::where('company_id', $companyId)->where('status', 'active')->where('level', 3)->whereIn('account_class', ['asset', 'liability', 'equity'])->orderBy('code')->get(),
            'customers' => Customer::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'operationalAccounts' => Account::where('company_id', $companyId)->where('status', 'active')->orderBy('account_name')->get(),
        ];
    }
}
