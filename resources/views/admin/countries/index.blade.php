@extends('admin.layout')

@section('title', 'Country Master')

@section('content')
<div class="container-fluid dg-page" id="dg-country-master">
    <h1 class="h4 mb-3">Country Master</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.countries.store') }}" class="card card-body mb-3">@csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label">Country Name</label><input class="form-control" name="name" required maxlength="100"></div>
            <div class="col-md-3"><label class="form-label">ISO Code</label><input class="form-control text-uppercase" name="iso_code" required minlength="2" maxlength="2"></div>
            <div class="col-md-2"><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add Country</button></div>
        </div>
    </form>
    <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Name</th><th>ISO Code</th><th>Status</th><th>Update</th></tr></thead><tbody>
        @foreach($countries as $country)
            <tr><form method="POST" action="{{ route('admin.countries.update', $country) }}">@csrf @method('PUT')
                <td><input class="form-control" name="name" value="{{ $country->name }}" required maxlength="100"></td>
                <td><input class="form-control text-uppercase" name="iso_code" value="{{ $country->iso_code }}" required minlength="2" maxlength="2"></td>
                <td><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked($country->is_active)> Active</label></td>
                <td><button class="btn btn-outline-primary btn-sm">Save</button></td>
            </form></tr>
        @endforeach
    </tbody></table></div>
    {{ $countries->links() }}
</div>
@endsection
