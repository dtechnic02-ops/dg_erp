@extends('company.layout')

@section('title', 'Edit Staff')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Edit Staff</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end">
                    <a href="{{ route('company.users.index') }}" class="btn btn-outline-secondary dg-btn">Back to Staff</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Staff Details</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('company.users.update', $user->id) }}" class="row g-3">
                            @csrf

                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control dg-input" value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" class="form-control dg-input" value="{{ $user->email }}" disabled readonly>
                            </div>

                            <div class="col-md-6">
                                <label for="job_role" class="form-label">Job Role</label>
                                <select name="job_role" id="job_role" class="form-select dg-select" required>
                                    @foreach(\App\Services\JobRoleVisibilityService::jobRoles() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('job_role', $user->job_role) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary dg-btn">Update Staff</button>
                                <a href="{{ route('company.users.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
