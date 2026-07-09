@extends('company.layout')

@section('content')

<h2>Choose Plan</h2>

@if(session('success'))
    <p style="color:lightgreen;">{{ session('success') }}</p>
@endif

<form method="POST" enctype="multipart/form-data">
    @csrf

    <select name="plan_id" required>
        @foreach($plans as $plan)
            <option value="{{ $plan->id }}">
                {{ $plan->name }} - {{ $plan->user_limit }} Users
            </option>
        @endforeach
    </select>

    <br><br>

    <label>Upload Payment Screenshot:</label><br>
    <input type="file" name="screenshot" required>

    <br><br>

    <button style="background:#3b82f6;color:white;padding:8px 15px;border:none;">
        Submit Payment
    </button>

</form>

@endsection