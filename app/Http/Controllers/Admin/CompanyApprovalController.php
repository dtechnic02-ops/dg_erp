<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyRegistration;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class CompanyApprovalController extends Controller
{
    // 🔥 LIST (Admin + Super Staff)
    public function index()
    {
        abort_unless(auth()->check(), 403);
        abort_unless(in_array(auth()->user()->role_id, [1,4]), 403);

        $registrations = CompanyRegistration::latest()->paginate(10);

        return view('admin.registrations', compact('registrations'));
    }

    // 🔥 APPROVE
    public function approve($id)
    {
        abort_unless(in_array(auth()->user()->role_id, [1,4]), 403);

        $reg = CompanyRegistration::findOrFail($id);

        // ❗ already processed
        if ($reg->status !== 'pending') {
            return back()->with('error', 'Already processed!');
        }

        // ❗ mobile check
        if (!$reg->mobile_no) {
            return back()->with('error', 'Mobile number missing.');
        }

        // ✅ COMPANY CREATE
        $company = Company::firstOrCreate(
            ['email' => $reg->email],
            [
                'company_name' => $reg->company_name,
                'mobile' => $reg->mobile_no,
                'status' => 'active'
            ]
        );

        // 🔥 FOLDER CREATE
        $folderPath = public_path('companies/' . $company->id);

        if (File::exists($folderPath)) {
            return back()->with('error', 'Folder already exists!');
        }

        File::makeDirectory($folderPath, 0755, true);

        // ✅ USER CREATE
        User::firstOrCreate(
            ['email' => $reg->email],
            [
                'company_id' => $company->id,
                'name' => $reg->full_name,
                'password' => Hash::make($reg->password ?? '123456'),
                'role_id' => 2
            ]
        );

        // ✅ UPDATE REGISTRATION
        $reg->update(['status' => 'approved']);

        return redirect()->route('admin.registrations')
            ->with('success', 'Company Approved Successfully');
    }

    // 🔥 REJECT
    public function reject($id)
    {
        abort_unless(in_array(auth()->user()->role_id, [1,4]), 403);

        $reg = CompanyRegistration::findOrFail($id);

        if ($reg->status !== 'pending') {
            return back()->with('error', 'Already processed!');
        }

        $reg->update(['status' => 'rejected']);

        return redirect()->route('admin.registrations')
            ->with('success', 'Company Rejected');
    }
}