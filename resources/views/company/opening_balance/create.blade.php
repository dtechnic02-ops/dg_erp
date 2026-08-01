@extends('company.layout')
@section('title','Create Opening Balance')
@section('content')<div class="dg-page"><header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between"><h1 class="h4 mb-0">Create Opening Balance</h1><a class="btn btn-outline-secondary dg-btn" href="{{ route('company.opening-balances.index') }}">Back</a></div></header><main class="dg-container"><div class="container-fluid"><form method="POST" action="{{ route('company.opening-balances.store') }}">@include('company.opening_balance.partials.form')</form></div></main></div>@endsection
