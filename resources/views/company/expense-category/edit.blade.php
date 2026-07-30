@extends('company.layout')

@section('title', 'Edit Expense Category')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Expense Category</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Expense category toolbar">
                        <a href="{{ route('company.expense-category.index') }}" class="btn btn-outline-secondary dg-btn">Category List</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('company.expense-category.update', $category->id) }}" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Category Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control dg-input" value="{{ old('name', $category->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="chart_account_id">Chart Account <span class="text-danger">*</span></label>
                                    <select name="chart_account_id" id="chart_account_id" class="form-select dg-select" required>
                                        <option value="">Select Expense Chart Account</option>
                                        @foreach ($chartAccounts as $chartAccount)
                                            <option value="{{ $chartAccount->id }}" @selected(old('chart_account_id', $category->chart_account_id) == $chartAccount->id)>
                                                {{ $chartAccount->code }} - {{ $chartAccount->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select dg-select" required>
                                        <option value="1" @selected(old('status', $category->status) == 1)>Active</option>
                                        <option value="0" @selected(old('status', $category->status) == 0)>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea name="description" id="description" rows="3" class="form-control dg-input">{{ old('description', $category->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Category</button>
                            <a href="{{ route('company.expense-category.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
