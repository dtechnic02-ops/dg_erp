<?php



namespace App\Http\Controllers\Company;



use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;

use Illuminate\Routing\Controllers\HasMiddleware;

use App\Models\PartyAccount;

use App\Services\ValidationService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;



class PartyAccountController extends Controller implements HasMiddleware

{

    use AuthorizesSubscriptionModule;



    public static function middleware(): array

    {

        return self::subscriptionModuleMiddleware();

    }



    protected static function subscriptionModuleCode(): string

    {

        return 'loan';

    }



    private function filteredPartyQuery(Request $request)

    {

        $query = PartyAccount::where(

            'company_id',

            auth()->user()->company_id

        );



        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('name', 'like', '%' . $request->search . '%')

                    ->orWhere('phone', 'like', '%' . $request->search . '%')

                    ->orWhere('account_no', 'like', '%' . $request->search . '%');

            });

        }



        return $query;

    }



    private function generateAccountNo(int $companyId): string

    {

        $year = now()->year;



        $last = PartyAccount::withTrashed()

            ->where('company_id', $companyId)

            ->latest('id')

            ->first();



        $next = 1;



        if ($last) {

            $parts = explode('-', $last->account_no);

            $next = ((int) end($parts)) + 1;

        }



        return 'PAR-'

            . $companyId

            . '-'

            . $year

            . '-'

            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

    }



    private function uploadFolder(int $companyId): string

    {

        return 'companies/' . $companyId . '/party-accounts';

    }



    private function ensureUploadFolder(string $folder): void

    {

        if (!file_exists(public_path($folder))) {

            mkdir(public_path($folder), 0777, true);

        }

    }



    private function uploadFile($file, string $folder): ?string

    {

        if (!$file) {

            return null;

        }



        $name = time()

            . '_'

            . rand(1000, 9999)

            . '_'

            . $file->getClientOriginalName();



        $file->move(public_path($folder), $name);



        return $folder . '/' . $name;

    }



    private function deleteFile(?string $path): void

    {

        if ($path && file_exists(public_path($path))) {

            unlink(public_path($path));

        }

    }



    private function validatePartyRequest(Request $request): void

    {

        $request->validate([

            'name'             => ValidationService::requiredString(255),

            'type'             => 'required|in:bank,person,customer,supplier,company,other',

            'phone'            => ValidationService::phone(),

            'due_date'         => 'nullable|date',

            'photo'            => ValidationService::image(),

            'id_card'          => ValidationService::document(5120),

            'document'         => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'status'           => 'nullable|in:0,1',

        ]);

    }



    public function index(Request $request)

    {

        $totalCurrentBalance = $this->filteredPartyQuery($request)->sum('current_balance');



        $allowedPerPage = [10, 25, 50, 100, 200, 500];

        $perPage = (int) $request->get('per_page', 10);



        if (!in_array($perPage, $allowedPerPage, true)) {

            $perPage = 10;

        }



        $parties = $this->filteredPartyQuery($request)

            ->latest()

            ->paginate($perPage)

            ->withQueryString();



        $accountNo = $this->generateAccountNo(auth()->user()->company_id);



        return view(

            'company.party-account.index',

            compact('parties', 'totalCurrentBalance', 'perPage', 'accountNo')

        );

    }



    public function create()

    {

        return redirect()->route('company.party-account.index');

    }



    public function store(Request $request)

    {

        $this->validatePartyRequest($request);



        try {

            DB::transaction(function () use ($request) {

                $companyId = auth()->user()->company_id;

                $folder = $this->uploadFolder($companyId);

                $this->ensureUploadFolder($folder);



                PartyAccount::create([

                    'company_id'       => $companyId,

                    'account_no'       => $this->generateAccountNo($companyId),

                    'name'             => $request->name,

                    'phone'            => $request->phone,

                    'address'          => $request->address,

                    'type'             => $request->type,

                    'photo'            => $this->uploadFile($request->file('photo'), $folder),

                    'id_card'          => $this->uploadFile($request->file('id_card'), $folder),

                    'document'         => $this->uploadFile($request->file('document'), $folder),

                    'note'             => $request->note,

                    'due_date'         => $request->due_date,

                    'created_by'       => auth()->id(),

                    'status'           => (int) ($request->status ?? PartyAccount::STATUS_ACTIVE),

                ]);

            });

        } catch (\Exception $exception) {

            return back()

                ->withInput()

                ->with('error', $exception->getMessage());

        }



        return back()->with('success', 'Party created.');

    }



    public function show($id)

    {

        $party = PartyAccount::where(

            'company_id',

            auth()->user()->company_id

        )->findOrFail($id);



        return view(

            'company.party-account.show',

            compact('party')

        );

    }



    public function update(Request $request, $id)

    {

        $this->validatePartyRequest($request);



        $party = PartyAccount::where('company_id', auth()->user()->company_id)

            ->findOrFail($id);



        DB::transaction(function () use ($request, $party) {

            $folder = $this->uploadFolder($party->company_id);

            $this->ensureUploadFolder($folder);



            $data = [

                'name'        => $request->name,

                'phone'       => $request->phone,

                'address'     => $request->address,

                'type'        => $request->type,

                'note'        => $request->note,

                'due_date'    => $request->due_date,

                'status'      => (int) ($request->status ?? PartyAccount::STATUS_ACTIVE),

                'updated_by'  => auth()->id(),

            ];



            if ($request->hasFile('photo')) {

                $this->deleteFile($party->photo);

                $data['photo'] = $this->uploadFile($request->file('photo'), $folder);

            }



            if ($request->hasFile('id_card')) {

                $this->deleteFile($party->id_card);

                $data['id_card'] = $this->uploadFile($request->file('id_card'), $folder);

            }



            if ($request->hasFile('document')) {

                $this->deleteFile($party->document);

                $data['document'] = $this->uploadFile($request->file('document'), $folder);

            }



            $party->update($data);

        });



        return back()->with('success', 'Party updated.');

    }



    public function destroy($id)

    {

        $party = PartyAccount::where('company_id', auth()->user()->company_id)

            ->findOrFail($id);



        $party->deleted_by = auth()->id();

        $party->save();

        $party->delete();



        return redirect()

            ->route('company.party-account.index')

            ->with('success', 'Party deleted.');

    }

}


