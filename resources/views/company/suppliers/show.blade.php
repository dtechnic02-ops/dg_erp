@extends('company.layout')

@section('title','Supplier Details')

@push('styles')

<link
rel="stylesheet"
href="{{ asset('assets/company/css/profile-show.css') }}">

@endpush

@section('content')

@php

$company = auth()->user()->company;

@endphp

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">

<div>

<h4 class="mb-0">

Supplier Details

</h4>

<small class="text-muted">

Supplier Profile Information

</small>

</div>

<button

onclick="window.print()"

class="btn btn-primary">

<i class="fa fa-print me-1"></i>

Print

</button>

</div>



<div class="profile-wrapper">

<div class="row g-3 align-items-stretch">




{{-- ===========================================
Company Information
=========================================== --}}

<div class="col-lg-5">

<div class="profile-card h-100">

<div class="card-body">

<div class="d-flex">

<div class="me-3">

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

class="company-logo"

alt="Company Logo">

@else

<img

src="{{ asset('images/no-image.png') }}"

class="company-logo"

alt="Company Logo">

@endif

</div>

<div class="flex-grow-1">

<h3 class="company-title">

{{ $company->company_name ?? 'DG ERP SOLUTIONS PVT. LTD.' }}

</h3>

<table class="table table-borderless table-sm mb-0 company-table">

<tr>

<th>

Address

</th>

<td>

{{ $company->address ?? '-' }}

</td>

</tr>

<tr>

<th>

Mobile

</th>

<td>

{{ $company->mobile ?? '-' }}

</td>

</tr>

<tr>

<th>

Email

</th>

<td>

{{ $company->email ?? '-' }}

</td>

</tr>

<tr>

<th>

Website

</th>

<td>

{{ $company->website ?? '-' }}

</td>

</tr>

<tr>

<th>

Tax No

</th>

<td>

 {{ $company->pan_number ?: '-' }}

</td>

</tr>

<tr>

<th>

VAT

</th>

<td>

  {{ $company->vat_number ?: '-' }}

</td>

</tr>

</table>

</div>

</div>

</div>

</div>

</div>




{{-- ===========================================
Supplier Photo
=========================================== --}}

<div class="col-lg-2">

<div class="profile-card h-100">

<div class="card-body text-center">

@if($supplier->image_path)

<img

src="{{ asset($supplier->image_path) }}"

class="profile-photo">

@else

<img

src="{{ asset('images/no-image.png') }}"

class="profile-photo">

@endif

</div>

</div>

</div>




{{-- ===========================================
SUPPLIER DETAILS Summary
=========================================== --}}

<div class="col-lg-5">

<div class="profile-card h-100">

<div class="card-header profile-header">

SUPPLIER DETAILS

</div>

<div class="card-body">

<table class="table table-borderless table-sm summary-table">

<tr>

<th>

Supplier Name

</th>

<td>

{{ $supplier->name }}

</td>

</tr>

<tr>

<th>

Opening Balance

</th>

<td class="fw-bold text-primary">

{{ number_format($supplier->opening_balance,2) }}

</td>

</tr>

<tr>

<th>

Current Balance

</th>

<td class="fw-bold text-success">

{{ number_format($supplier->current_balance,2) }}

</td>

</tr>

<tr>

<th>

Created Date

</th>

<td>

{{ optional($supplier->created_at)->format('Y-m-d') }}

</td>

</tr>

<tr>

<th>

Print Date

</th>

<td>

{{ now()->format('Y-m-d') }}

</td>

</tr>

<tr>

<th>

Printed By

</th>

<td>

{{ auth()->user()->name }}

</td>

</tr>

</table>

</div>

</div>

</div>

</div>




{{-- ===========================================
BODY START
=========================================== --}}

<div class="row g-3 mt-1">
   {{-- ===========================================
SUPPLIER INFORMATION
=========================================== --}}

<div class="col-lg-4">

<div class="profile-card h-100">

<div class="card-header bg-primary text-white fw-bold">

1. SUPPLIER INFORMATION

</div>

<div class="card-body p-0">

<table class="table table-striped table-hover mb-0 profile-table">

<tr>

<th width="45%">

Customer Code

</th>

<td>

S-{{ str_pad($supplier->id,5,'0',STR_PAD_LEFT) }}

</td>

</tr>

<tr>

<th>

Supplier Name

</th>

<td>

{{ $supplier->name }}

</td>

</tr>

<tr>

<th>

Authority Name

</th>

<td>

{{ $supplier->authority_name ?: '-' }}

</td>

</tr>

<tr>

<th>

Mobile

</th>

<td>

{{ $supplier->mobile ?: '-' }}

</td>

</tr>

<tr>

<th>

Telephone

</th>

<td>

{{ $supplier->telephone ?: '-' }}

</td>

</tr>

<tr>

<th>

Fax No

</th>

<td>

{{ $supplier->fax_no ?: '-' }}

</td>

</tr>

<tr>

<th>

Email

</th>

<td>

{{ $supplier->email ?: '-' }}

</td>

</tr>

<tr>

<th>

Address

</th>

<td>

{{ $supplier->address ?: '-' }}

</td>

</tr>

</table>

</div>

</div>

</div>



{{-- ===========================================
BANK & ADDITIONAL INFORMATION
=========================================== --}}

<div class="col-lg-4">

<div class="profile-card h-100">

<div class="card-header bg-success text-white fw-bold">

2. BANK & ADDITIONAL INFORMATION

</div>

<div class="card-body">

<h6 class="text-success fw-bold mb-3">

BANK INFORMATION

</h6>

<table class="table table-borderless profile-table">

<tr>

<th width="45%">

Bank Name

</th>

<td>

{{ $supplier->bank_name ?: '-' }}

</td>

</tr>

<tr>

<th>

Account No

</th>

<td>

{{ $supplier->bank_account_no ?: '-' }}

</td>

</tr>

</table>

<hr>

<h6 class="text-success fw-bold mb-3">

ADDITIONAL INFORMATION

</h6>

<table class="table table-borderless profile-table mb-0">

<tr>

<th width="45%">

Tax No

</th>

<td>

{{ $supplier->tax_no ?: '-' }}

</td>

</tr>

<tr>

<th>

Website

</th>

<td>

{{ $supplier->website ?: '-' }}

</td>

</tr>

<tr>

<th>

Status

</th>

<td>

@if($supplier->status=='active')

<span class="badge bg-success">

Active

</span>

@else

<span class="badge bg-danger">

Inactive

</span>

@endif

</td>

</tr>

</table>

</div>

</div>

</div>



{{-- ===========================================
ACCOUNT SUMMARY
=========================================== --}}

<div class="col-lg-4">

<div class="profile-card h-100">

<div class="card-header bg-warning text-dark fw-bold">

3. ACCOUNT SUMMARY

</div>

<div class="card-body">

<table class="table table-borderless profile-table">

<tr>

<th>

Opening Balance

</th>

<td class="text-end fw-bold text-primary">

{{ number_format($supplier->opening_balance,2) }}

</td>

</tr>

<tr>

<th>

Current Balance

</th>

<td class="text-end fw-bold text-success">

{{ number_format($supplier->current_balance,2) }}

</td>

</tr>

</table>

<hr>

<h6 class="fw-bold">

NOTE

</h6>

<div class="border rounded p-3 bg-light">
{{ $supplier->note ?: '-' }}

</div>

</div>

</div>

</div> {{-- Footer Row --}}

<div class="row mt-5">

    <div class="col-md-4 text-center">

        <div class="signature-line"></div>

        <h6 class="mb-1">

            Prepared By

        </h6>

        <small class="text-muted">

            {{ auth()->user()->name }}

        </small>

    </div>

    <div class="col-md-4 text-center">

        <div class="signature-line"></div>

        <h6 class="mb-1">

            Checked By

        </h6>

        <small class="text-muted">

            ____________________

        </small>

    </div>

    <div class="col-md-4 text-center">

        <div class="signature-line"></div>

        <h6 class="mb-1">

            Approved By

        </h6>

        <small class="text-muted">

            ____________________

        </small>

    </div>

</div>

</div>

</div>

@endsection