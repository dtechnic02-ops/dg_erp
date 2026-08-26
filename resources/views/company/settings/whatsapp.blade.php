@extends('company.layout')

@section('title', 'WhatsApp Share Settings')

@section('content')
<div class="dg-page">
    <div class="dg-toolbar d-flex justify-content-between align-items-center">
        <h4 class="mb-0">WhatsApp Share Settings</h4>
        <a href="{{ route('company.profile') }}" class="btn btn-secondary dg-btn">Back</a>
    </div>

    <div class="dg-container">
        @if(session('success'))
            <div class="alert alert-success dg-alert">{{ session('success') }}</div>
        @endif

        <section class="dg-section">
            <div class="card dg-card">
                <div class="card-header dg-card-header">Normal WhatsApp Share</div>
                <div class="card-body dg-card-body">
                    <p class="text-muted">WhatsApp opens with the prepared message. The user manually sends the message.</p>

                    <form method="POST" action="{{ route('company.settings.whatsapp.update') }}">
                        @csrf
                        @method('PUT')

                        <label for="dgWhatsappEnabled" class="form-label">Enable WhatsApp Share</label>
                        <select id="dgWhatsappEnabled" name="is_enabled" class="form-select dg-select" required>
                            <option value="1" @selected((bool) old('is_enabled', $setting->is_enabled))>Enabled</option>
                            <option value="0" @selected(! (bool) old('is_enabled', $setting->is_enabled))>Disabled</option>
                        </select>
                        @error('is_enabled')<div class="text-danger mt-1">{{ $message }}</div>@enderror

                        <button type="submit" class="btn btn-primary dg-btn mt-3">Save</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
