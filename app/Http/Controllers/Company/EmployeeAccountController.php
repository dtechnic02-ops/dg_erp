<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\EmployeeAccount;
use App\Services\ValidationService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeAccountController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'hr';
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('employee.view');

        $totalOpeningDueSalary = $this->filteredEmployeeQuery($request)
            ->sum('opening_due_salary');

        $allowedPerPage = [10, 25, 50, 100, 200, 500];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $employees = $this->filteredEmployeeQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('company.employee-account.index', compact(
            'employees',
            'totalOpeningDueSalary',
            'perPage'
        ));
    }

    public function print(Request $request)
    {
        $this->authorizeCompanyPermission('employee.view');

        $employees = $this->filteredEmployeeQuery($request)
            ->latest()
            ->get();

        $totalEmployees = $employees->count();
        $totalOpeningDueSalary = (float) $employees->sum('opening_due_salary');
        $totalBasicSalary = (float) $employees->sum('basic_salary');

        return view('company.employee-account.print', compact(
            'employees',
            'totalEmployees',
            'totalOpeningDueSalary',
            'totalBasicSalary'
        ));
    }

    private function filteredEmployeeQuery(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = EmployeeAccount::where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('designation', 'like', '%' . $search . '%')
                    ->orWhere('department', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where(
                'status',
                $request->status === 'active'
                    ? EmployeeAccount::STATUS_ACTIVE
                    : EmployeeAccount::STATUS_INACTIVE
            );
        }

        return $query;
    }

    public function create()
    {
        $this->authorizeCompanyPermission('employee.create');

        $companyId = auth()->user()->company_id;
        $employeeCode = $this->previewEmployeeCode($companyId);

        return view('company.employee-account.create', compact('employeeCode'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('employee.create');

        $companyId = auth()->user()->company_id;

        $request->validate([
            'first_name' => 'required',
            'email' => ValidationService::email(),
            'phone' => ValidationService::phone(),
            'emergency_phone' => ValidationService::phone(),
            'dob' => 'nullable|date',
            'joining_date' => 'required|date',
            'basic_salary' => ValidationService::amount(),
            'opening_due_salary' => ValidationService::amount(),
            'photo' => ValidationService::image(),
            'cv_attachment' => ValidationService::document(),
            'id_document' => ValidationService::document(),
            'contract_document' => ValidationService::document(),
        ]);

        $folder = 'companies/' . $companyId . '/employees';

        try {
            DB::transaction(function () use ($request, $companyId, $folder) {
                $employeeCode = $this->generateEmployeeCode($companyId);

                $photo = null;
                $cv = null;
                $idDocument = null;
                $contract = null;

                if ($request->hasFile('photo')) {
                    $photo = FileUploadService::uploadImage(
                        $request->file('photo'),
                        $folder,
                        800
                    );
                }

                if ($request->hasFile('cv_attachment')) {
                    $cv = FileUploadService::uploadFile(
                        $request->file('cv_attachment'),
                        $folder
                    );
                }

                if ($request->hasFile('id_document')) {
                    $idDocument = FileUploadService::uploadFile(
                        $request->file('id_document'),
                        $folder
                    );
                }

                if ($request->hasFile('contract_document')) {
                    $contract = FileUploadService::uploadFile(
                        $request->file('contract_document'),
                        $folder
                    );
                }

                EmployeeAccount::create([
                    'company_id' => $companyId,
                    'employee_code' => $employeeCode,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'dob' => $request->dob,
                    'joining_date' => $request->joining_date,
                    'designation' => $request->designation,
                    'department' => $request->department,
                    'post' => $request->post,
                    'employment_type' => $request->employment_type ?? 'permanent',
                    'basic_salary' => (float) ($request->basic_salary ?? 0),
                    'salary_type' => $request->salary_type ?? 'monthly',
                    'opening_due_salary' => (float) ($request->opening_due_salary ?? 0),
                    'bank_name' => $request->bank_name,
                    'bank_account_no' => $request->bank_account_no,
                    'account_holder_name' => $request->account_holder_name,
                    'cit_no' => $request->cit_no,
                    'pan_no' => $request->pan_no,
                    'emergency_contact' => $request->emergency_contact,
                    'emergency_phone' => $request->emergency_phone,
                    'photo' => $photo,
                    'cv_attachment' => $cv,
                    'id_document' => $idDocument,
                    'contract_document' => $contract,
                    'note' => $request->note,
                    'created_by' => auth()->id(),
                    'status' => EmployeeAccount::STATUS_ACTIVE,
                ]);
            });

            return redirect()
                ->route('company.employee-account.index')
                ->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('employee.view');

        $employee = EmployeeAccount::with(['creator', 'updater'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.employee-account.show', compact('employee'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('employee.edit');

        $employee = EmployeeAccount::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.employee-account.edit', compact('employee'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('employee.edit');

        $companyId = auth()->user()->company_id;

        $request->validate([
            'first_name' => 'required',
            'email' => ValidationService::email(),
            'phone' => ValidationService::phone(),
            'emergency_phone' => ValidationService::phone(),
            'basic_salary' => ValidationService::amount(),
            'opening_due_salary' => ValidationService::amount(),
            'dob' => 'nullable|date',
            'joining_date' => 'required|date',
            'photo' => ValidationService::image(51200),
            'cv_attachment' => ValidationService::document(51200),
            'id_document' => ValidationService::document(51200),
            'contract_document' => ValidationService::document(51200),
        ]);

        $employee = EmployeeAccount::where('company_id', $companyId)
            ->findOrFail($id);

        $folder = 'companies/' . $companyId . '/employees';

        try {
            DB::transaction(function () use ($request, $employee, $folder) {
                $photo = FileUploadService::replaceImage(
                    $request,
                    'photo',
                    $employee->photo,
                    $folder,
                    800
                );

                $cvAttachment = FileUploadService::replaceFile(
                    $request,
                    'cv_attachment',
                    $employee->cv_attachment,
                    $folder
                );

                $idDocument = FileUploadService::replaceFile(
                    $request,
                    'id_document',
                    $employee->id_document,
                    $folder
                );

                $contractDocument = FileUploadService::replaceFile(
                    $request,
                    'contract_document',
                    $employee->contract_document,
                    $folder
                );

                $employee->update([
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'dob' => $request->dob,
                    'joining_date' => $request->joining_date,
                    'designation' => $request->designation,
                    'department' => $request->department,
                    'post' => $request->post,
                    'employment_type' => $request->employment_type ?? 'permanent',
                    'basic_salary' => (float) ($request->basic_salary ?? 0),
                    'salary_type' => $request->salary_type ?? 'monthly',
                    'opening_due_salary' => (float) ($request->opening_due_salary ?? 0),
                    'bank_name' => $request->bank_name,
                    'bank_account_no' => $request->bank_account_no,
                    'account_holder_name' => $request->account_holder_name,
                    'cit_no' => $request->cit_no,
                    'pan_no' => $request->pan_no,
                    'emergency_contact' => $request->emergency_contact,
                    'emergency_phone' => $request->emergency_phone,
                    'photo' => $photo,
                    'cv_attachment' => $cvAttachment,
                    'id_document' => $idDocument,
                    'contract_document' => $contractDocument,
                    'note' => $request->note,
                    'updated_by' => auth()->id(),
                ]);
            });

            return redirect()
                ->route('company.employee-account.index')
                ->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        $this->authorizeCompanyPermission('employee.status');

        $request->validate([
            'status' => ['required', Rule::in([
                (string) EmployeeAccount::STATUS_ACTIVE,
                (string) EmployeeAccount::STATUS_INACTIVE,
            ])],
        ]);

        $employee = EmployeeAccount::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $newStatus = (int) $request->status;

        if ((int) $employee->status === $newStatus) {
            return back()->with('success', 'Employee status is already up to date.');
        }

        $employee->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        $message = $newStatus === EmployeeAccount::STATUS_ACTIVE
            ? 'Employee activated successfully.'
            : 'Employee deactivated successfully. Existing HR history remains visible.';

        return back()->with('success', $message);
    }

    public function destroy($id)
    {
        $this->authorizeCompanyPermission('employee.delete');

        try {
            $employee = EmployeeAccount::where('company_id', auth()->user()->company_id)
                ->findOrFail($id);

            if ($employee->hasHrDependencies()) {
                $dependencies = implode(', ', $employee->dependencySummary());

                return back()->with(
                    'error',
                    'Employee cannot be deleted because related HR records exist: '
                    . $dependencies
                    . '. Deactivate the employee instead.'
                );
            }

            $files = [
                $employee->photo,
                $employee->cv_attachment,
                $employee->id_document,
                $employee->contract_document,
            ];

            DB::transaction(function () use ($employee, $files) {
                foreach ($files as $file) {
                    FileUploadService::deleteFile($file);
                }

                $employee->delete();
            });

            return back()->with('success', 'Employee deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function previewEmployeeCode(int $companyId): string
    {
        return $this->buildEmployeeCode($companyId, $this->nextEmployeeSequence($companyId));
    }

    private function generateEmployeeCode(int $companyId): string
    {
        $lastEmployee = EmployeeAccount::where('company_id', $companyId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($lastEmployee) {
            preg_match('/(\d+)$/', $lastEmployee->employee_code, $match);
            $next = isset($match[1]) ? ((int) $match[1]) + 1 : 1;
        }

        return $this->buildEmployeeCode($companyId, $next);
    }

    private function nextEmployeeSequence(int $companyId): int
    {
        $lastEmployee = EmployeeAccount::where('company_id', $companyId)
            ->latest('id')
            ->first();

        if (!$lastEmployee) {
            return 1;
        }

        preg_match('/(\d+)$/', $lastEmployee->employee_code, $match);

        return isset($match[1]) ? ((int) $match[1]) + 1 : 1;
    }

    private function buildEmployeeCode(int $companyId, int $sequence): string
    {
        return 'EMP-'
            . $companyId
            . '-'
            . now()->year
            . '-'
            . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
