@extends('company.layout')
@section('content')
<div class="dg-page"><header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between"><h1 class="h4 mb-0">Create Quotation</h1><a href="{{ route('company.quotations.index') }}" class="btn btn-outline-secondary dg-btn">Quotation List</a></div></header><main class="dg-container"><div class="container-fluid"><form id="dgForm" method="POST" action="{{ route('company.quotations.store') }}">@include('company.quotations.partials.form')</form></div></main></div>
@endsection
