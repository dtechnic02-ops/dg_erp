@extends('admin.layout')

@section('title', 'Subscription Plans')

@section('content')

@php
    $edit = request('edit');
    $editPlan = $plans->where('id', $edit)->first();
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Subscription Plans</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">{{ $editPlan ? 'Edit Plan' : 'Add Plan' }}</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ $editPlan ? route('admin.subscription-plans.update', $editPlan->id) : route('admin.subscription-plans.store') }}" class="dg-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Plan Name</label>
                                    <input type="text" name="name" class="form-control dg-input" value="{{ old('name', $editPlan->name ?? '') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Staff Limit</label>
                                    <input type="number" name="staff_limit" class="form-control dg-input" value="{{ old('staff_limit', $editPlan->staff_limit ?? '') }}" required min="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control dg-input" value="{{ old('sort_order', $editPlan->sort_order ?? 0) }}" min="0">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Hidden Modules</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (config('subscription.hidden_module_codes') as $module)
                                            <label class="dg-check">
                                                <input type="checkbox" name="hidden_modules[]" value="{{ $module }}" @checked(in_array($module, old('hidden_modules', $editPlan->hidden_modules ?? [])))>
                                                {{ strtoupper($module) }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                @if ($editPlan)
                                    <div class="col-12">
                                        <label class="form-label">Billing Options</label>
                                        @foreach ($billingCycles as $cycle)
                                            @php $option = $editPlan->billingOptions->firstWhere('billing_cycle_id', $cycle->id); @endphp
                                            <div class="row g-2 align-items-center mb-2">
                                                <div class="col-md-4">
                                                    <label class="dg-check mb-0">
                                                        <input type="checkbox" name="billing_options[{{ $cycle->id }}][enabled]" value="1" @checked($option?->is_active)>
                                                        {{ $cycle->name }}
                                                    </label>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" step="0.01" name="billing_options[{{ $cycle->id }}][price]" class="form-control dg-input" value="{{ old('billing_options.'.$cycle->id.'.price', $option->price ?? 0) }}" placeholder="Price">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">{{ $editPlan ? 'Update Plan' : 'Add Plan' }}</button>
                                    @if ($editPlan)
                                        <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Plan List</h2>
                    </header>
                    <div class="card-body dg-card-body p-0">
                        <div class="table-responsive">
                            <table class="table dg-table mb-0">
                                <thead class="dg-head">
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Staff</th>
                                        <th>Hidden Modules</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($plans as $plan)
                                        <tr class="dg-row">
                                            <td>{{ $plan->name }}</td>
                                            <td>{{ $plan->code }}</td>
                                            <td>{{ $plan->staff_limit }}</td>
                                            <td>{{ empty($plan->hidden_modules) ? 'None' : implode(', ', $plan->hidden_modules) }}</td>
                                            <td>{{ $plan->is_active ? 'Active' : 'Inactive' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.subscription-plans.index', ['edit' => $plan->id]) }}" class="btn btn-sm btn-outline-primary dg-btn">Edit</a>
                                                @if ($plan->is_active)
                                                    <form action="{{ route('admin.subscription-plans.deactivate', $plan->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-warning dg-btn">Deactivate</button></form>
                                                @else
                                                    <form action="{{ route('admin.subscription-plans.activate', $plan->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-success dg-btn">Activate</button></form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row"><td colspan="6" class="text-center">No plans found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>
@endsection
