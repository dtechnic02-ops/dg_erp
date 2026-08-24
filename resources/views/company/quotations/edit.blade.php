@extends('company.layout')
@section('content')
<div class="dg-page"><header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between"><h1 class="h4 mb-0">Edit Draft Quotation</h1><a href="{{ route('company.quotations.show', $quotation) }}" class="btn btn-outline-secondary dg-btn">Back</a></div></header><main class="dg-container"><div class="container-fluid"><form id="dgForm" method="POST" action="{{ route('company.quotations.update', $quotation) }}">@include('company.quotations.partials.form')</form></div></main></div>
@endsection
