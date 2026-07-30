@extends('admin.layout')

@section('title', 'Platform Access')

@section('content')
    <div class="dg-page">
        <div class="dg-card card">
            <div class="dg-card-body card-body text-center py-5">
                <i class="bi bi-shield-lock fs-1 text-muted" aria-hidden="true"></i>
                <h2 class="dg-page-title mt-3">No Platform Access Assigned</h2>
                <p class="dg-page-subtitle mb-0">
                    No platform permission has been assigned to your account. Please contact the Super Admin.
                </p>
            </div>
        </div>
    </div>
@endsection
