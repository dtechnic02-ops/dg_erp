@extends('company.layout')

@section('title', 'Income Categories')

@section('content')

@php
    $user = auth()->user();
    $canManage = $user && ($user->role_id == 2 || $user->hasPermission('manage_income_categories'));
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Income Categories</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group flex-wrap" aria-label="Income category toolbar">
                        <a href="{{ route('company.income.index') }}" class="btn btn-sm btn-outline-secondary dg-btn">Income List</a>
                        @if ($canManage)
                            <a href="{{ route('company.income-category.create') }}" class="btn btn-sm btn-success dg-btn">Add Category</a>
                        @endif
                    </nav>
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

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>
                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.income-category.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Category name">
                                </div>
                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="">All</option>
                                        <option value="1" @selected(request('status') === '1')>Active</option>
                                        <option value="0" @selected(request('status') === '0')>Inactive</option>
                                    </select>
                                </div>
                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.income-category.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Category List</h2>
                    </header>
                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Category Name</th>
                                        <th scope="col">Description</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-action-col">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($categories as $category)
                                        <tr class="dg-row">
                                            <td>{{ $categories->firstItem() + $loop->index }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->note ?: '-' }}</td>
                                            <td class="dg-col-status">
                                                @if ($category->isActive())
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="dg-action-col">
                                                @if ($canManage)
                                                    <div class="dg-action-group" role="group" aria-label="Category actions for {{ $category->name }}">
                                                        <a href="{{ route('company.income-category.edit', $category->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">Edit</a>
                                                        <form method="POST" action="{{ route('company.income-category.delete', $category->id) }}" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger dg-action-btn">Delete</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="5" class="text-center">No categories found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            <div class="mt-3">
                {{ $categories->links() }}
            </div>

        </div>
    </main>
</div>

@endsection
