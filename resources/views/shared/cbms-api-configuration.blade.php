@extends($platform ? 'admin.layout' : 'company.layout')
@section('title','CBMS API Configuration')
@section('content')
<div class="container py-4"><h2>IRD / CBMS Settings</h2><p>CBMS Status: <strong>{{ $isModeActive?'Active':'Inactive' }}</strong></p><p>API Configuration: <strong>{{ $configuration?'Configured':'Not Configured' }}</strong></p><p>Credentials Configured: <strong>{{ $credentialsConfigured?'Yes':'No' }}</strong></p><p>Seller PAN (Company Profile): <strong>{{ $sellerPan ?: 'Not configured' }}</strong></p><p>Last Updated: <strong>{{ $configuration?->updated_at?->format('Y-m-d H:i:s') ?: 'Never' }}</strong></p><p>Readiness: <strong>{{ $isModeActive && $credentialsConfigured && $sellerPan ? 'Configuration Ready' : 'Not Ready' }}</strong></p><p>Verification: <strong>Not Verified</strong></p>
<form method="POST" action="{{ $platform ? route('admin.company.cbms-api.update',$company) : route('company.settings.cbms-api.update') }}">@csrf @method('PUT')
<label>Environment</label><select name="environment" class="form-select"><option value="test" @selected(old('environment',$configuration?->environment)==='test')>Test</option><option value="production" @selected(old('environment',$configuration?->environment)==='production')>Production</option></select>
<label class="mt-3">IRD Taxpayer Login Username</label><input name="client_identifier" class="form-control" value="{{ old('client_identifier',$configuration?->client_identifier) }}" autocomplete="off">
<label class="mt-3">IRD Taxpayer Login Password</label><input type="password" name="credential" class="form-control" value="" placeholder="{{ $configuration?'************':'Enter password' }}" autocomplete="new-password"><small>{{ $configuration?'Saved password is never displayed. Leave blank to preserve it.':'' }}</small>
@foreach($errors->all() as $error)<div class="text-danger">{{ $error }}</div>@endforeach<button class="btn btn-primary mt-3">Save Configuration</button></form></div>
@endsection
