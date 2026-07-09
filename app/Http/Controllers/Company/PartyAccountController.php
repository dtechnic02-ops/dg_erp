<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\PartyAccount;

class PartyAccountController extends Controller
{

/**
* INDEX
*/

public function index()
{

$parties = PartyAccount::where(

'company_id',

auth()->user()->company_id

)

->latest()

->paginate(20);

return view(

'company.party-account.index',

compact(

'parties'

)

);

}


/**
* CREATE
*/

public function create()
{

$companyId =
auth()->user()->company_id;

$year =
now()->year;

$last =
PartyAccount::where(

'company_id',

$companyId

)

->latest('id')

->first();

$next=1;

if($last){

$parts=
explode(
'-',
$last->account_no
);

$next=
((int) end($parts))
+1;

}

$accountNo=

'PAR-'

.$companyId

.'-'

.$year

.'-'

.str_pad(

$next,

4,

'0',

STR_PAD_LEFT

);

return view(

'company.party-account.create',

compact(

'accountNo'

)

);

}


/**
* STORE
*/

public function store(Request $request)
{

$request->validate([

'name'=>

'required',

'type'=>

'required',

'opening_balance'=>

'nullable|numeric',

'photo'=>

'nullable|

mimes:jpg,jpeg,png|

max:5120',

'id_card'=>

'nullable|

mimes:jpg,jpeg,png,pdf|

max:5120',

'document'=>

'nullable|

mimes:pdf|

max:10240'

]);

DB::transaction(

function()

use($request){

$companyId=
auth()->user()->company_id;

$folder=

'companies/'

.$companyId

.'/party-accounts';

if(

!file_exists(

public_path(

$folder

)

)

){

mkdir(

public_path(
$folder
),

0777,

true

);

}

function uploadFile(
$file,
$folder
){

if(!$file){

return null;

}

$name=

time()

.'_'

.rand(

1000,

9999

)

.'_'

.$file

->getClientOriginalName();

$file->move(

public_path(
$folder
),

$name

);

return

$folder

.'/'

.$name;

}

$photo=

uploadFile(

$request->file(
'photo'
),

$folder

);

$idCard=

uploadFile(

$request->file(
'id_card'
),

$folder

);

$document=

uploadFile(

$request->file(
'document'
),

$folder

);

$year=
now()->year;

$last=
PartyAccount::where(

'company_id',

$companyId

)

->latest('id')

->first();

$next=1;

if($last){

$parts=
explode(
'-',
$last->account_no
);

$next=
((int) end($parts))
+1;

}

$accountNo=

'PAR-'

.$companyId

.'-'

.$year

.'-'

.str_pad(

$next,

4,

'0',

STR_PAD_LEFT

);

PartyAccount::create([

'company_id'=>

$companyId,

'account_no'=>

$accountNo,

'name'=>

$request->name,

'phone'=>

$request->phone,

'address'=>

$request->address,

'opening_balance'=>

$request->opening_balance ??0,

'current_balance'=>

$request->opening_balance ??0,

'type'=>

$request->type,

'photo'=>

$photo,

'id_card'=>

$idCard,

'document'=>

$document,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

}

);

return redirect()

->route(

'company.party-account.index'

)

->with(

'success',

'Party created.'

);

}


/**
* SHOW
*/

public function show($id)
{

$party=

PartyAccount::where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.party-account.show',

compact(

'party'

)

);

}

}