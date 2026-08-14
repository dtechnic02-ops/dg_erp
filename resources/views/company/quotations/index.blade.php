@extends('company.layout')
@section('content')
<div class="dg-page"><header class="dg-toolbar"><div class="container-fluid d-flex justify-content-between"><h1 class="h4 mb-0">Quotations</h1>@if(auth()->user()->hasPermission('create_quotation'))<a href="{{ route('company.quotations.create') }}" class="btn btn-primary dg-btn">Create Quotation</a>@endif</div></header><main class="dg-container"><div class="container-fluid">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif<div class="table-responsive"><table class="table dg-table"><thead><tr><th>Quotation No</th><th>Date</th><th>Customer</th><th>Amount</th><th>Status</th><th>Generated Invoice</th><th>Actions</th></tr></thead><tbody>
@forelse($quotations as $quotation)
<tr><td>{{ $quotation->quotation_no }}</td><td>{{ $quotation->quotation_date->format('d-m-Y') }} @include('company.components.nepali-date-display', ['adDate' => $quotation->quotation_date])</td><td>{{ $quotation->customer?->name }}</td><td>{{ number_format((float)$quotation->grand_total, 2) }}</td><td>{{ ucfirst($quotation->status) }}</td><td>@if($quotation->salesInvoice)<a href="{{ route('company.sales.show',$quotation->salesInvoice) }}">{{ $quotation->salesInvoice->invoice_no }}</a>@else — @endif</td><td>
<a href="{{ route('company.quotations.show',$quotation) }}" class="btn btn-sm btn-outline-primary">View</a>
@if($quotation->status === 'draft')
@if(auth()->user()->hasPermission('edit_quotation'))<a href="{{ route('company.quotations.edit',$quotation) }}" class="btn btn-sm btn-outline-secondary">Edit</a>@endif
@if(auth()->user()->hasPermission('approve_quotation'))<form method="POST" action="{{ route('company.quotations.approve',$quotation) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>@endif
@if(auth()->user()->hasPermission('delete_quotation'))<form method="POST" action="{{ route('company.quotations.destroy',$quotation) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endif
@elseif($quotation->status === 'approved' && auth()->user()->hasPermission('generate_quotation_invoice'))
<a href="{{ route('company.quotations.show',$quotation) }}#generate-invoice" class="btn btn-sm btn-outline-success">Generate Invoice</a>
@elseif($quotation->salesInvoice)
<a href="{{ route('company.sales.show',$quotation->salesInvoice) }}" class="btn btn-sm btn-outline-success">View Invoice</a>
@endif
@if(auth()->user()->hasPermission('print_quotation'))<a href="{{ route('company.quotations.print',$quotation) }}" class="btn btn-sm btn-outline-dark">Print</a>@endif
</td></tr>
@empty<tr><td colspan="7" class="text-center">No quotations found.</td></tr>@endforelse
</tbody></table></div>{{ $quotations->links() }}</div></main></div>
@endsection
