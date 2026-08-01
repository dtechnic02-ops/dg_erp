@extends('company.layout')
@section('title', 'Edit Draft Journal')
@section('content')
<div class="dg-page">
    <header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0">Edit Draft Journal {{ $journal->journal_no }}</h1><a href="{{ route('company.journal.show', $journal->id) }}" class="btn btn-outline-secondary dg-btn">Back</a></div></header>
    <div class="container-fluid py-3">
        @include('company.journal.partials.phase-one-form', ['action' => route('company.journal.update', $journal->id), 'method' => 'PUT', 'journal' => $journal])
    </div>
</div>
@endsection
