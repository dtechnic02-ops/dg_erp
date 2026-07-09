<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashAccount;

class CashAccountController extends Controller
{
    // =========================================
    // 🔥 LIST
    // =========================================

    public function index(Request $request)
    {
        $query = CashAccount::where(
            'company_id',
            auth()->user()->company_id
        );

        // 🔍 SEARCH
        if ($request->search) {

            $query->where(function($q) use ($request){

                $q->where(
                    'account_name',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'account_number',
                    'like',
                    '%' . $request->search . '%'
                );
            });
        }

        $cashAccounts = $query->latest()->get();

        return view(
            'company.cash_accounts.index',
            compact('cashAccounts')
        );
    }


    // =========================================
    // 🔥 STORE
    // =========================================

    public function store(Request $request)
    {
        $request->validate([

            'account_name' => 'required',
        ]);

        CashAccount::create([

            'company_id' =>
                auth()->user()->company_id,

            'account_name' =>
                $request->account_name,

            'account_number' =>
                $request->account_number,

            'opening_balance' =>
                $request->opening_balance ?? 0,

            'note' =>
                $request->note,

            'status' =>
                $request->status ?? 'active',
        ]);

        return back()->with(
            'success',
            'Cash Account Added Successfully'
        );
    }


    // =========================================
    // 🔥 UPDATE
    // =========================================

    public function update(Request $request, $id)
    {
        $cash = CashAccount::where('id', $id)

            ->where(
                'company_id',
                auth()->user()->company_id
            )

            ->firstOrFail();

        $request->validate([

            'account_name' => 'required',
        ]);

        $cash->update([

            'account_name' =>
                $request->account_name,

            'account_number' =>
                $request->account_number,

            'opening_balance' =>
                $request->opening_balance ?? 0,

            'note' =>
                $request->note,

            'status' =>
                $request->status,
        ]);

        return back()->with(
            'success',
            'Cash Account Updated Successfully'
        );
    }


    // =========================================
    // 🔥 DELETE
    // =========================================

    public function destroy($id)
    {
        $cash = CashAccount::where('id', $id)

            ->where(
                'company_id',
                auth()->user()->company_id
            )

            ->firstOrFail();

        $cash->delete();

        return back()->with(
            'success',
            'Cash Account Deleted Successfully'
        );
    }
}

