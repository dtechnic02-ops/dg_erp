<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    public function index(Request $request, $id)
    {
        $params = array_merge(
            $request->query(),
            ['supplier_id' => $id]
        );

        return redirect()->route(
            'company.supplier-statement.index',
            $params
        );
    }
}
