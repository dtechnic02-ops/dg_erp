<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Vat;
use Illuminate\Http\Request;

class VatController extends Controller
{
    public function index()
    {
        $vats = Vat::where('company_id', auth()->user()->company_id)
            ->latest()
            ->get();

        return view('company.vats.index', compact('vats'));
    }

    public function store(Request $request)
    {
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
        $vat = Vat::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $vat->delete();

        return redirect()
            ->route('company.vats.index')
            ->with('success', 'VAT deleted successfully.');
    }
}