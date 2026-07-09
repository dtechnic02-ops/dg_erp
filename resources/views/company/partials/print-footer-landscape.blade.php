@php

$company = \App\Models\Company::find(
auth()->user()->company_id
);

@endphp


<style>

/* FOOTER */

.print-footer{

margin-top:12px;

padding-top:8px;

border-top:

1px solid #d1d5db;

font-size:11px;

}


/* TABLE */

.footer-table{

width:100%;

border-collapse:collapse;

}


.footer-table td{

padding:2px;

vertical-align:top;

}


/* SIGNATURE */

.signature-row{

display:flex;

justify-content:space-between;

gap:30px;

margin-top:20px;

}


.sign-box{

width:180px;

text-align:center;

font-size:11px;

}


.sign-line{

border-top:

1px solid #555;

margin-bottom:4px;

padding-top:3px;

}


/* PRINT */

@media print{

.print-footer{

break-inside:avoid;

}

}

</style>



<div class="print-footer">


<table class="footer-table">

<tr>


<!-- LEFT -->

<td width="60%">

@if(
!empty(
$company->print_note
)
)

{!! nl2br(
e(
$company->print_note
)
) !!}

@else

Thank you for your business.

@endif

</td>



<!-- RIGHT -->

<td align="right">

Generated:

{{ now()->format(
'd M Y H:i'
) }}

<br>

Printed By:

{{ auth()->user()->name ?? '' }}

</td>


</tr>

</table>



<div class="signature-row">


<div class="sign-box">

<div class="sign-line">

</div>

Prepared By

</div>


<div class="sign-box">

<div class="sign-line">

</div>

Authorized By

</div>


<div class="sign-box">

<div class="sign-line">

</div>

Received By

</div>


</div>


</div>

