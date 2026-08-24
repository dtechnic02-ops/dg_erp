<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Vat;
use Illuminate\Http\Request;

class VatController extends Controller
{
    use AuthorizesCompanyPermission;

    public function index()
    {
        $this->authorizeCompanyPermission('view_vat');

        $vats = Vat::where('company_id', auth()->user()->company_id)
            ->latest()
            ->get();

        return view('company.vats.index', compact('vats'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_vat');

        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        Vat::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'rate' => $request->rate,
            'is_default' => $request->is_default ?? 0,
        ]);

        return redirect()
            ->route('company.vats.index')
            ->with('success', 'VAT created successfully.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_vat');

        $vat = Vat::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        $vat->update([
            'name' => $request->name,
            'rate' => $request->rate,
            'is_default' => $request->is_default ?? 0,
        ]);

        return redirect()
            ->route('company.vats.index')
            ->with('success', 'VAT updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeCompanyPermission('delete_vat');

        $vat = Vat::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $vat->delete();

        return redirect()
            ->route('company.vats.index')
            ->with('success', 'VAT deleted successfully.');
    }
}