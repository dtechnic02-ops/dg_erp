@extends('company.layout')
@section('title', 'Opening Balances')
@section('content')
<div class="dg-page">
 <header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between"><h1 class="h4 mb-0">Opening Balances</h1>
 @if(auth()->user()->hasPermission('opening-balance.create'))<a class="btn btn-success dg-btn" href="{{ route('company.opening-balances.create') }}">New Opening Balance</a>@endif</div></header>
 <main class="dg-container"><div class="container-fluid">
 @if(session('success'))<div class="alert alert-success dg-alert">{{ session('success') }}</div>@endif
 <section class="dg-section"><article class="card dg-card"><div class="table-responsive"><table class="table dg-table mb-0">
 <thead><tr><th>Reference</th><th>Financial Year</th><th>Business Date</th><th>Type</th><th>Status</th><th>Locked</th><th></th></tr></thead>
 <tbody>@forelse($items as $item)<tr><td>{{ $item->reference_number }}</td><td>{{ $item->financialYear?->name }}</td><td>{{ $item->business_date->format('Y-m-d') }}</td><td>{{ str_replace('_',' ',ucfirst($item->type)) }}</td><td>{{ ucfirst($item->status) }}</td><td>{{ $item->is_locked ? 'Yes' : 'No' }}</td><td><a class="btn btn-sm btn-outline-primary dg-btn" href="{{ route('company.opening-balances.show',$item) }}">View</a></td></tr>@empty<tr><td colspan="7" class="text-center">No Opening Balances found.</td></tr>@endforelse</tbody>
 </table></div></article></section>{{ $items->links() }}
 </div></main>
</div>
@endsection
