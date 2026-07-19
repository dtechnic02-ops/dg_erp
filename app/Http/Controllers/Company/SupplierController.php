<?php


namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

use App\Services\FileUploadService;
use App\Services\ValidationService;

use App\Services\SupplierTransactionService;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
class SupplierController extends Controller
{
    // 🔥 SHARED FILTERED QUERY
    // Used by index() and any other action (e.g. summary totals)
    // so the filter logic is defined in exactly one place.
    private function filteredSupplierQuery(Request $request)
    {
        $query = Supplier::where(

'company_id',

auth()->user()->company_id

)

->where(

'status',

'!=',

'inactive'

);

        // 🔍 SEARCH
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('mobile', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        return $query;
    }

    // 🔥 LIST
    public function index(Request $request)
    {
        $totalCurrentBalance = $this->filteredSupplierQuery($request)
            ->sum('current_balance');

        $suppliers = $this->filteredSupplierQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view(
            'company.suppliers.index',
            compact('suppliers', 'totalCurrentBalance')
        );
    }



    // 🔥 STORE
public function store(Request $request)
{
    $request->validate([

        'name' => 'required|max:255',

        'image_path' =>
            ValidationService::image(),

        'opening_balance' =>
            ValidationService::amount(),

    ]);

    $imagePath = null;

    $folder =
        'companies/' .
        auth()->user()->company_id .
        '/suppliers';

    if ($request->hasFile('image_path'))
    {
        $imagePath =
            FileUploadService::uploadImage(
                $request->file('image_path'),
                $folder,
                800
            );
    }
DB::beginTransaction();

try {
    $supplier = Supplier::create([

        'company_id' =>
            auth()->user()->company_id,

        'name' =>
            $request->name,

        'authority_name' =>
            $request->authority_name,

        'mobile' =>
            $request->mobile,

        'telephone' =>
            $request->telephone,

        'fax_no' =>
            $request->fax_no,

        'email' =>
            $request->email,

        'website' =>
            $request->website,

        'address' =>
            $request->address,

        'tax_no' =>
            $request->tax_no,

        'opening_balance' =>
            $request->opening_balance ?? 0,


        'bank_name' =>
            $request->bank_name,

        'bank_account_no' =>
            $request->bank_account_no,

        'note' =>
            $request->note,

        'image_path' =>
            $imagePath,

        'status' =>
            $request->status ?? 'active',

    ]);
    if ($supplier->opening_balance > 0)
{
    $activeFy = FinancialYear::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'is_active',
        1
    )
    ->first();

    if ($activeFy)
    {
        SupplierTransactionService::createTransaction([

            'company_id' =>
                auth()->user()->company_id,

            'financial_year_id' =>
                $activeFy->id,

            'supplier_id' =>
                $supplier->id,

            'transaction_date' =>
                $activeFy->start_date,

            'voucher_no' =>
                'OB-' . $supplier->id,

            'reference_type' =>
                'opening_balance',

            'reference_id' =>
                $supplier->id,

            'reference_no' =>
                null,

            'description' =>
                'Opening Balance',

            'debit' =>
                $supplier->opening_balance,

            'credit' =>
                0,

            'created_by' =>
                auth()->id(),

            'status' =>
                1,

        ]);
    }
}

    DB::commit();

return back()->with(
    'success',
    'Supplier Added Successfully'
);
}
catch (\Exception $e)
{
    DB::rollBack();

    FileUploadService::deleteFile(
        $imagePath
    );

    throw $e;
}

}
    


public function update(
    Request $request,
    $id
)

{
     DB::beginTransaction();

    try{
    $supplier = Supplier::where(
        'id',
        $id
    )
    ->where(
        'company_id',
        auth()->user()->company_id
    )
    ->firstOrFail();

    // Opening Balance Change Protection
    if (
        $supplier->current_balance !=
        $supplier->opening_balance
        &&
        $request->opening_balance !=
        $supplier->opening_balance
    )
    {
        return back()->with(
            'error',
            'Opening balance cannot be changed after transactions.'
        );
    }

    $request->validate([

        'name' => 'required|max:255',

        'opening_balance' =>
            ValidationService::amount(),

        'image_path' =>
            ValidationService::image(),

    ]);

    $data = [

        'name' =>
            $request->name,

        'authority_name' =>
            $request->authority_name,

        'mobile' =>
            $request->mobile,

        'telephone' =>
            $request->telephone,

        'fax_no' =>
            $request->fax_no,

        'email' =>
            $request->email,

        'website' =>
            $request->website,

        'address' =>
            $request->address,

        'tax_no' =>
            $request->tax_no,

        'opening_balance' =>
            $request->opening_balance ?? 0,

        'bank_name' =>
            $request->bank_name,

        'bank_account_no' =>
            $request->bank_account_no,

        'note' =>
            $request->note,

        'status' =>
            $request->status,

    ];

    $folder =
        'companies/' .
        auth()->user()->company_id .
        '/suppliers';

    $data['image_path'] =
        FileUploadService::replaceImage(
            $request,
            'image_path',
            $supplier->image_path,
            $folder,
            800
        );

       $supplier->update($data);

    DB::commit();

    return back()->with(
        'success',
        'Supplier Updated Successfully'
    );

}
catch(\Exception $e){

    DB::rollBack();

    throw $e;
}
}

/* =====================

SHOW

===================== */

public function show($id)
{
    $supplier = Supplier::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    return view(

        'company.suppliers.show',

        compact(

            'supplier'

        )

    );
}


/* =====================

PRINT (LIST)

Reuses the SAME filteredSupplierQuery() used by
index(), so Print always reflects only the
currently filtered suppliers (never the full table
unless no filter is applied).

===================== */

public function print(Request $request)
{
    $suppliers = $this->filteredSupplierQuery($request)
        ->latest()
        ->get();

    $totalSuppliers = $suppliers->count();

    $totalOpeningBalance = $suppliers->sum('opening_balance');

    $totalCurrentBalance = $suppliers->sum('current_balance');

    return view(
        'company.suppliers.print',
        compact(
            'suppliers',
            'totalSuppliers',
            'totalOpeningBalance',
            'totalCurrentBalance'
        )
    );
}


/* =====================

PRINT PROFILE

Reuses the existing show.blade.php view with
$print = true, exactly like the Customer module.

===================== */

public function printProfile($id)
{
    $companyId = auth()->user()->company_id;

    $supplier = Supplier::where('company_id', $companyId)
        ->findOrFail($id);

    $print = true;

    return view(
        'company.suppliers.show',
        compact('supplier', 'print')
    );
}


public function destroy($id)
{
   
$supplier = Supplier::where(
    'id',
    $id
)
->where(
    'company_id',
    auth()->user()->company_id
)
->firstOrFail();
   if (
    $supplier->purchaseInvoices()->exists()
    ||
    $supplier->purchaseReturns()->exists()
    ||
    $supplier->transactions()->exists()
)
{
    return back()->with(
        'error',
        'Supplier has transactions and cannot be deleted.'
    );
}

    FileUploadService::deleteFile(
        $supplier->image_path
    );

    $supplier->update([
        'status' => 'inactive'
    ]);

    return back()->with(
        'success',
        'Supplier Inactivated Successfully.'
    );
}
}