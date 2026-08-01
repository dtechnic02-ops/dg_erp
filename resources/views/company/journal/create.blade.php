@extends('company.layout')
@section('title', 'Create Draft Journal')
@section('content')
<div class="dg-page">
    <header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0">Create Draft Journal</h1><a href="{{ route('company.journal.index') }}" class="btn btn-outline-secondary dg-btn">Back</a></div></header>
    <div class="container-fluid py-3">
        @include('company.journal.partials.phase-one-form', ['action' => route('company.journal.store'), 'method' => 'POST', 'journal' => null])
    </div>
</div>
@endsection
