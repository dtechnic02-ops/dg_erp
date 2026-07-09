@php

$company = \App\Models\Company::find(
auth()->user()->company_id
);

@endphp


<style>

@page{

size:A4 landscape;

margin:8mm;

}


/* HEADER */

.print-header{

width:100%;

border-bottom:

1px solid #d1d5db;

padding-bottom:6px;

margin-bottom:8px;

}


.header-table td{

padding:2px;

vertical-align:top;

}


/* LOGO */

.logo-box{

width:80px;

}


.logo{

width:60px;

height:60px;

object-fit:contain;

}


/* COMPANY */

.company-box{

width:80%;

text-align:center;

padding-left:8px;

}


.company-name{

font-size:18px;

font-weight:700;

margin-bottom:2px;

text-align:center;

}


.company-address{

font-size:11px;

line-height:1.4;

text-align:center;

padding-right:10px;

}


/* CONTACT */

.info-box{

width:210px;

font-size:11px;

line-height:1.5;

text-align:right;

}


/* PRINT BUTTON */

.print-btn{

position:fixed;

top:10px;

right:10px;

z-index:999;

}


@media print{

.print-btn{

display:none;

}

}



</style>



<div class="print-header">

<table class="header-table">

<tr>


<!-- LOGO -->

<td class="logo-box">

@if(
$company &&
$company->logo_path
)

<img

src="{{ asset(

'companies/' .

$company->id .

'/' .

$company->logo_path

) }}"

class="logo">

@endif

</td>



<!-- COMPANY -->

<td class="company-box">

<div class="company-name">

{{ $company->company_name ?? '' }}

</div>


<div class="company-address">

{{ $company->address ?? '' }}

<br>

@if(
!empty(
$company->pan_number
)
)

<b>

PAN:

</b>

{{ $company->pan_number }}

@endif

</div>

</td>



<!-- CONTACT -->

<td class="info-box">

<div>

<span class="label">

Telephone:

</span>

{{ $company->telephone ?? '' }}

</div>


<div>

<span class="label">

Mobile:

</span>

{{ $company->mobile ?? '' }}

</div>


<div>

<span class="label">

Email:

</span>

{{ $company->email ?? '' }}

</div>


<div>

<span class="label">

Website:

</span>

{{ $company->website ?? '' }}

</div>

</td>


</tr>

</table>

</div>

