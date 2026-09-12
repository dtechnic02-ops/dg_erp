@extends('company.layout')

@section('title', 'Nepal Tax Settings')

@section('content')
<div class="dg-page">
    <div class="dg-toolbar d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Nepal Tax Settings</h4>
        <a href="{{ route('company.profile') }}" class="btn btn-secondary dg-btn">Back</a>
    </div>

    <div class="dg-container">
        @if(session('success'))
            <div class="alert alert-success dg-alert">{{ session('success') }}</div>
        @endif

        <section class="dg-section">
            <div class="card dg-card">
                <div class="card-header dg-card-header">Seller Tax Identity</div>
                <div class="card-body dg-card-body">
                    @if($isNepalCompany)
                        <div class="mb-4">
                            <h5>Seller Tax Identity</h5>
                            <p class="text-muted">Effective Tax Mode: <strong>{{ $taxIdentity['effective_tax_mode'] }}</strong></p>
                            @if(auth()->user()->hasPermission('edit_company_profile'))
                                @php
                                    $vatRegisteredDefault = (bool) old('is_vat_registered', $taxSetting->is_vat_registered);
                                @endphp
                                <form method="POST" action="{{ route('company.settings.ird-cbms.tax-identity.update') }}" id="dgNepalTaxIdentityForm">
                                    @csrf
                                    @method('PUT')
                                    <label for="dgSellerPan" class="form-label">PAN Number</label>
                                    <input id="dgSellerPan" name="pan_number" class="form-control" value="{{ old('pan_number', $company->pan_number) }}" required>
                                    @error('pan_number')<div class="text-danger mt-1">{{ $message }}</div>@enderror

                                    <label for="dgVatRegistered" class="form-label mt-3">VAT Registered</label>
                                    <select id="dgVatRegistered" name="is_vat_registered" class="form-select dg-select" required>
                                        <option value="0" @selected(! $vatRegisteredDefault)>No</option>
                                        <option value="1" @selected($vatRegisteredDefault)>Yes</option>
                                    </select>
                                    @error('is_vat_registered')<div class="text-danger mt-1">{{ $message }}</div>@enderror

                                    <label for="dgVatNumberDisplay" class="form-label mt-3">VAT Number</label>
                                    <input id="dgVatNumberDisplay" type="text" class="form-control" readonly
                                           aria-describedby="dgVatMirrorHelp">
                                    <div id="dgVatMirrorHelp" class="form-text text-muted">
                                        When VAT Registered is Yes, the VAT number is the same as the PAN number.
                                    </div>

                                    <button type="submit" class="btn btn-primary dg-btn mt-3">Save Tax Identity</button>
                                </form>
                                <script>
                                    (function () {
                                        var pan = document.getElementById('dgSellerPan');
                                        var vatReg = document.getElementById('dgVatRegistered');
                                        var vatDisplay = document.getElementById('dgVatNumberDisplay');
                                        function syncVatDisplay() {
                                            var on = vatReg.value === '1';
                                            if (!on) {
                                                vatDisplay.value = '';
                                                vatDisplay.placeholder = 'Not VAT registered';
                                                return;
                                            }
                                            vatDisplay.placeholder = '';
                                            vatDisplay.value = pan.value.trim();
                                        }
                                        pan.addEventListener('input', syncVatDisplay);
                                        vatReg.addEventListener('change', syncVatDisplay);
                                        syncVatDisplay();
                                    })();
                                </script>
                            @endif
                        </div>

                        @if($isModeActive)
                            <p class="text-muted mb-0">Nepal IRD/CBMS status: <strong>ON</strong> (managed by platform administration)</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">
                            Nepal IRD/CBMS mode is unavailable because this company's Country Master ISO code is not NP.
                        </p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
