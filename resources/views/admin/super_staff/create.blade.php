@extends('admin.layout')

@section('title', 'Add Super Staff')

@section('content')
    <div class="dg-page">
        <header class="dg-toolbar">
            <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
                <h1 class="h4 mb-0">Add Super Staff</h1>
                <a href="{{ route('admin.super-staff.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
            </div>
        </header>

        <main class="dg-container">
            <div class="container-fluid">
                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header"><h2 class="h6 mb-0">Account Details</h2></header>
                        <div class="card-body dg-card-body">
                            <form method="POST" action="{{ route('admin.super-staff.store') }}" class="dg-form">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Name</label>
                                        <input id="name" name="name" type="text" class="form-control dg-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input id="email" name="email" type="email" class="form-control dg-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <input id="password" name="password" type="password" class="form-control dg-input @error('password') is-invalid @enderror" minlength="8" required>
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control dg-input" minlength="8" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary dg-btn">Create Super Staff</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
@endsection
