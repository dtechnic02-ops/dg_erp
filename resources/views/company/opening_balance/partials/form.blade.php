@php
    $editing = isset($openingBalance);
    $rows = old('lines', $editing ? $openingBalance->lines->toArray() : [['debit'=>'0.0000','credit'=>'0.0000'],['debit'=>'0.0000','credit'=>'0.0000']]);
    $selectedType = old('type', $openingBalance->type ?? 'initial');
    $storedOperationalLine = $editing && $openingBalance->type === 'new_account'
        ? $openingBalance->lines->firstWhere('operational_account_id', '!=', null)
        : null;
    $selectedNewAccountId = old('new_account_id', $storedOperationalLine?->operational_account_id);
    $selectedNewAccountAmount = old('new_account_amount', $storedOperationalLine?->debit);
    $selectedNewAccount = $newAccountOperationalAccounts->firstWhere('id', (int) $selectedNewAccountId);
    $previewCurrency = $selectedNewAccount?->currency ?: 'AED';
@endphp
@if($errors->any())<div class="alert alert-danger dg-alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@csrf
@if($editing) @method('PUT') @else <input type="hidden" name="request_key" value="{{ old('request_key', (string) Illuminate\Support\Str::uuid()) }}"> @endif
<section class="dg-section"><article class="card dg-card"><header class="card-header dg-card-header"><h2 class="h6 mb-0">Opening Balance Information</h2></header><div class="card-body dg-card-body"><div class="row g-3">
<div class="col-md-3"><label class="dg-label" for="financial_year_id">Financial Year</label><select class="form-select dg-select" id="financial_year_id" name="financial_year_id" required>@foreach($financialYears as $fy)<option value="{{ $fy->id }}" @selected(old('financial_year_id',$openingBalance->financial_year_id ?? null)==$fy->id)>{{ $fy->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="dg-label" for="business_date">Business Date</label><input class="form-control dg-input" type="date" id="business_date" name="business_date" value="{{ old('business_date',isset($openingBalance)?$openingBalance->business_date->format('Y-m-d'):'') }}" required></div>
@include('company.components.nepali-date-field', ['adInputId' => 'business_date', 'adDate' => old('business_date', $openingBalance->business_date ?? null), 'columnClass' => 'col-md-3'])
<div class="col-md-3"><label class="dg-label" for="type">Type</label><select class="form-select dg-select" id="type" name="type">@foreach(\App\Models\OpeningBalance::TYPES as $type)<option value="{{ $type }}" @selected(old('type',$openingBalance->type ?? '')===$type)>{{ ucwords(str_replace('_',' ',$type)) }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="dg-label" for="reference_number">Reference</label><input class="form-control dg-input" id="reference_number" name="reference_number" value="{{ old('reference_number',$openingBalance->reference_number ?? '') }}" required></div>
<div class="col-12 {{ $selectedType === 'new_account' ? 'd-none' : '' }}" id="dg-ob-main-remarks"><label class="dg-label" for="remarks">Remarks</label><textarea class="form-control dg-input" id="remarks" name="remarks">{{ old('remarks',$openingBalance->remarks ?? '') }}</textarea></div>
</div></div></article></section>
<section class="dg-section {{ $selectedType === 'new_account' ? '' : 'd-none' }}" id="dg-ob-new-account-section"><article class="card dg-card"><header class="card-header dg-card-header"><h2 class="h6 mb-0">New Operational Account Opening</h2></header><div class="card-body dg-card-body"><div class="row g-3">
<div class="col-md-6"><label class="dg-label" for="new_account_id">Operational Account</label><select class="form-select dg-select" id="new_account_id" name="new_account_id"><option value="">Select Cash, Bank, ATM, or Wallet</option>@foreach($newAccountOperationalAccounts as $op)<option value="{{ $op->id }}" data-name="{{ $op->account_name }}" data-type="{{ $op->account_type }}" data-currency="{{ $op->currency ?: 'AED' }}" @selected((string)$selectedNewAccountId === (string)$op->id)>{{ $op->account_name }} — {{ $op->account_type }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="dg-label" for="new_account_amount">Opening Balance</label><input class="form-control dg-input" type="number" min="0.0001" step="0.0001" id="new_account_amount" name="new_account_amount" value="{{ $selectedNewAccountAmount }}"><div class="form-text">Debit opening only. Accounting lines are generated securely by the server.</div></div>
<div class="col-12"><label class="dg-label" for="new_account_remarks">Description</label><textarea class="form-control dg-input" id="new_account_remarks" name="remarks">{{ old('remarks',$openingBalance->remarks ?? '') }}</textarea></div>
<div class="col-12"><aside class="alert alert-light border dg-alert mb-0" aria-live="polite"><h3 class="h6">Posting Preview</h3><div class="d-flex justify-content-between"><span>Opening Balance</span><strong><span class="dg-ob-preview-currency">{{ $previewCurrency }}</span> <span class="dg-ob-preview-amount">{{ number_format((float)($selectedNewAccountAmount ?: 0), 2) }}</span></strong></div><hr><div class="d-flex justify-content-between"><span>Debit: <span id="dg-ob-preview-debit-name">{{ $selectedNewAccount?->account_name ?: 'Operational Account' }}</span> / <span id="dg-ob-preview-chart">{{ $selectedNewAccount ? ($selectedNewAccount->account_type === 'Cash' ? 'Cash in Hand' : 'Bank Accounts') : 'Mapped Chart Account' }}</span></span><strong><span class="dg-ob-preview-amount">{{ number_format((float)($selectedNewAccountAmount ?: 0), 2) }}</span></strong></div><div class="d-flex justify-content-between"><span>Credit: Opening Balance Equity</span><strong><span class="dg-ob-preview-amount">{{ number_format((float)($selectedNewAccountAmount ?: 0), 2) }}</span></strong></div><hr><div class="d-flex justify-content-between"><span>Difference</span><strong>0.00</strong></div></aside></div>
</div></div></article></section>
<section class="dg-section {{ $selectedType === 'new_account' ? 'd-none' : '' }}" id="dg-ob-lines-section"><article class="card dg-card"><header class="card-header dg-card-header d-flex justify-content-between"><h2 class="h6 mb-0">Opening Balance Voucher</h2><button type="button" class="btn btn-sm btn-outline-success dg-btn" id="dg-ob-add-line">Add Line</button></header><div class="table-responsive"><table class="table dg-table mb-0"><thead><tr><th>Account</th><th>Debit Amount</th><th>Credit Amount</th><th>Description / Narration</th><th></th></tr></thead><tbody id="dg-ob-lines">
@foreach($rows as $i=>$row)
@php
    $pickerValue = $row['account_picker'] ?? null;
    if (! $pickerValue && ! empty($row['operational_account_id'])) $pickerValue = 'operational:'.$row['operational_account_id'];
    if (! $pickerValue && ! empty($row['subledger_type']) && ! empty($row['subledger_id'])) $pickerValue = $row['subledger_type'].':'.$row['subledger_id'];
    if (! $pickerValue && ! empty($row['chart_account_id'])) $pickerValue = 'chart:'.$row['chart_account_id'];
@endphp
<tr><td><input class="form-control dg-input dg-ob-picker-search mb-1" type="search" placeholder="Search account" aria-label="Search Opening Balance accounts"><select class="form-select dg-select dg-ob-account-picker" name="lines[{{ $i }}][account_picker]" required><option value="">Select Account</option>@foreach($openingBalanceAccountPicker as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $option)<option value="{{ $option['value'] }}" @selected($pickerValue===$option['value'])>{{ $option['label'] }}</option>@endforeach</optgroup>@endforeach</select></td>
<td><input class="form-control dg-input dg-ob-debit" type="number" min="0" step="0.0001" name="lines[{{ $i }}][debit]" value="{{ $row['debit']??'0.0000' }}"></td><td><input class="form-control dg-input dg-ob-credit" type="number" min="0" step="0.0001" name="lines[{{ $i }}][credit]" value="{{ $row['credit']??'0.0000' }}"></td><td><input class="form-control dg-input" name="lines[{{ $i }}][description]" value="{{ $row['description']??'' }}"></td><td><button type="button" class="btn btn-sm btn-outline-danger dg-btn dg-ob-remove" aria-label="Remove line">×</button></td></tr>@endforeach
</tbody><tfoot><tr><th>Voucher Totals</th><th id="dg-ob-debit-total">0.0000</th><th id="dg-ob-credit-total">0.0000</th><th>Difference: <span id="dg-ob-difference">0.0000</span></th><th></th></tr></tfoot></table></div></article></section>
<button class="btn btn-primary dg-btn" type="submit">Save Draft</button>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const body=document.getElementById('dg-ob-lines');
    const type=document.getElementById('type');
    const linesSection=document.getElementById('dg-ob-lines-section');
    const newAccountSection=document.getElementById('dg-ob-new-account-section');
    const mainRemarks=document.getElementById('dg-ob-main-remarks');
    const newAccount=document.getElementById('new_account_id');
    const newAccountAmount=document.getElementById('new_account_amount');
    const recalc=()=>{let d=0,c=0;body.querySelectorAll('.dg-ob-debit').forEach(x=>d+=Number(x.value||0));body.querySelectorAll('.dg-ob-credit').forEach(x=>c+=Number(x.value||0));document.getElementById('dg-ob-debit-total').textContent=d.toFixed(4);document.getElementById('dg-ob-credit-total').textContent=c.toFixed(4);document.getElementById('dg-ob-difference').textContent=(d-c).toFixed(4)};
    const bindPickerSearch=(row)=>{
        const search=row.querySelector('.dg-ob-picker-search');
        const picker=row.querySelector('.dg-ob-account-picker');
        search.addEventListener('input',()=>{const term=search.value.trim().toLowerCase();picker.querySelectorAll('option').forEach(option=>{if(option.value)option.hidden=term!==''&&!option.textContent.toLowerCase().includes(term)});});
    };
    const refreshNewAccountPreview=()=>{
        const option=newAccount.options[newAccount.selectedIndex];
        const amount=Number(newAccountAmount.value||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
        document.querySelectorAll('.dg-ob-preview-amount').forEach(element=>element.textContent=amount);
        document.querySelectorAll('.dg-ob-preview-currency').forEach(element=>element.textContent=option?.dataset.currency||'AED');
        document.getElementById('dg-ob-preview-debit-name').textContent=option?.dataset.name||'Operational Account';
        document.getElementById('dg-ob-preview-chart').textContent=!option?.value?'Mapped Chart Account':(option.dataset.type==='Cash'?'Cash in Hand':'Bank Accounts');
    };
    const toggleType=()=>{
        const isNewAccount=type.value==='new_account';
        linesSection.classList.toggle('d-none',isNewAccount);
        newAccountSection.classList.toggle('d-none',!isNewAccount);
        mainRemarks.classList.toggle('d-none',isNewAccount);
        mainRemarks.querySelectorAll('textarea').forEach(element=>element.disabled=isNewAccount);
        linesSection.querySelectorAll('input,select,button').forEach(element=>element.disabled=isNewAccount);
        newAccountSection.querySelectorAll('input,select,textarea').forEach(element=>element.disabled=!isNewAccount);
        refreshNewAccountPreview();
    };
    body.addEventListener('input',recalc);
    body.addEventListener('click',e=>{if(e.target.classList.contains('dg-ob-remove')&&body.rows.length>2){e.target.closest('tr').remove();recalc()}});
    document.getElementById('dg-ob-add-line').addEventListener('click',()=>{const row=body.rows[0].cloneNode(true),i=body.rows.length;row.querySelectorAll('input,select').forEach(x=>{x.name=x.name.replace(/lines\[\d+\]/,`lines[${i}]`);if(x.tagName==='INPUT')x.value=x.classList.contains('dg-ob-debit')||x.classList.contains('dg-ob-credit')?'0.0000':'';else{x.selectedIndex=0;x.querySelectorAll('option').forEach(option=>option.hidden=false)}});body.appendChild(row);bindPickerSearch(row);recalc()});
    body.querySelectorAll('tr').forEach(bindPickerSearch);
    document.addEventListener('keydown',event=>{if(event.key==='F9'&&type.value!=='new_account'){event.preventDefault();const row=document.activeElement.closest('tr')||body.rows[0];row?.querySelector('.dg-ob-picker-search')?.focus();}});
    body.addEventListener('keydown',event=>{if(event.key!=='Enter'||event.target.tagName==='BUTTON')return;const row=event.target.closest('tr');if(!row)return;const fields=[...row.querySelectorAll('input:not([disabled]),select:not([disabled])')];const index=fields.indexOf(event.target);if(index>=0&&index<fields.length-1){event.preventDefault();fields[index+1].focus();}});
    type.addEventListener('change',toggleType);
    newAccount.addEventListener('change',refreshNewAccountPreview);
    newAccountAmount.addEventListener('input',refreshNewAccountPreview);
    recalc();
    toggleType();
});
</script>
