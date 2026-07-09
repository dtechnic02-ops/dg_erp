@extends('company.layout')

@section('content')

<style>
.container {
    max-width: 500px;
    margin: auto;
}

/* HEADER */
.profile-box {
    background: #e2e8f0;
    padding: 15px;
    border-radius: 12px;
    display: flex;
    gap: 15px;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.profile-img 
{

    width: 70px;

    height: 70px;

    border-radius: 50%;

    object-fit: contain;

    background: white;

    border: 2px solid #cbd5e1;

    padding: 3px;

}

.profile-signature {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 3px;
    background: white;
}

/* INFO */
.profile-info h3 {
    margin: 0;
    color: #1e293b;
}

.profile-info p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

/* CARD */
.card {
    background: white;
    margin-top: 15px;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* ROW */
.row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 12px 0;ँ
    border-bottom: 1px solid #d4dbe6;
}

/* LABEL */
.label {
    font-size: 13px;
    color: #080808;
    min-width: 120px;
}

/* INPUT */
.input {
    border: none;
    background: #87aed4;
    padding: 8px 10px;
    border-radius: 6px;
    width: 100%;
    color: #0f172a;
}

/* BUTTON */
.btn {
    margin-top: 15px;
    padding: 12px;
    width: 100%;
    border-radius: 8px;
    border: none;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.btn:hover {
    background: #2563eb;
}
</style>

<div class="container">

<!-- HEADER -->
<div class="profile-box">
<img
    src="{{

        $company->logo_path

        ? asset(

            'companies/' .

            $company->id .

            '/' .

            $company->logo_path

        )

        : asset('logo.png')

    }}"

    class="profile-img"
>

    <div class="profile-info">
        <h3>{{ $company->company_name ?? 'Company Name' }}</h3>
        <p>{{ $company->email ?? 'company@email.com' }}</p>
    </div>

</div>

<!-- SUCCESS -->
@if(session('success'))
    <p id="success-msg" style="color:green; margin-top:10px;">
        {{ session('success') }}
    </p>

    <script>
        setTimeout(() => {
            document.getElementById('success-msg').style.display = 'none';
        }, 3000);
    </script>
@endif

<!-- ERROR -->
@if($errors->any())
    <div style="color:red; margin-top:10px;">
        {{ $errors->first() }}
    </div>
@endif

<!-- FORM -->
<div class="card">

<form method="POST"
      action="{{ route('company.profile.update') }}"
      enctype="multipart/form-data">

@csrf

<!-- COMPANY NAME -->
<div class="row">
    <span class="label">Company Name</span>

    <input class="input"
           type="text"
           name="company_name"
           value="{{ $company->company_name }}">
</div>

<!-- EMAIL -->
<div class="row">
    <span class="label">Email</span>

    <input class="input"
           type="email"
           name="email"
           value="{{ $company->email }}">
</div>

<!-- MOBILE -->
<div class="row">
    <span class="label">Mobile</span>

    <input class="input"
           type="text"
           name="mobile"
           value="{{ $company->mobile }}">
</div>

<!-- TELEPHONE -->
<div class="row">
    <span class="label">Telephone</span>

    <input class="input"
           type="text"
           name="telephone"
           value="{{ $company->telephone }}">
</div>

<!-- WEBSITE -->
<div class="row">
    <span class="label">Website</span>

    <input class="input"
           type="text"
           name="website"
           value="{{ $company->website }}">
</div>

<!-- COUNTRY -->
<div class="row">
    <span class="label">Country</span>

    <input class="input"
           type="text"
           name="country"
           value="{{ $company->country }}">
</div>

<!-- LANGUAGE -->
<div class="row">
    <span class="label">Language</span>

    <input class="input"
           type="text"
           name="language"
           value="{{ $company->language }}">
</div>

<!-- PAN -->
<div class="row">
    <span class="label">PAN Number</span>

    <input class="input"
           type="text"
           name="pan_number"
           value="{{ $company->pan_number }}">
</div>

<!-- VAT -->
<div class="row">
    <span class="label">VAT Number</span>

    <input class="input"
           type="text"
           name="vat_number"
           value="{{ $company->vat_number }}">
</div>

<!-- ADDRESS -->
<div class="row">
    <span class="label">Address</span>

    <input class="input"
           type="text"
           name="address"
           value="{{ $company->address }}">
</div>

<!-- LOGO -->

<div class="row">

    <span class="label">

        Logo

    </span>

    <div style="width:100%;">

        <input type="file"
               name="logo"
               accept=".jpg,.jpeg,.png">

        

        <small style="color:#64748b;">

            JPG / PNG only • Max 20MB

        </small>

    </div>

</div>

<!-- SIGNATURE -->

<div class="row">

    <span class="label">

        Signature

    </span>

    <div style="width:100%;">

        <input type="file"
               name="signature"
               accept=".jpg,.jpeg,.png">

        @if($company->signature_path)

            <div style="margin-top:8px;">

                <img
                    src="{{ asset(
                        'companies/' .
                        $company->id .
                        '/' .
                        $company->signature_path
                    ) }}"

                    class="profile-signature">

            </div>

        @endif

        <small style="color:#64748b;">

            JPG / PNG only • Max 1MB

        </small>

    </div>

</div>

<!-- BUTTON -->
<button type="submit" class="btn">
    💾 Update Profile
</button>

</form>

</div>

</div>

@endsection