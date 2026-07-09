@extends('company.layout')

@section('title','Create Contra')

@section('content')

<div class="card">

    <div class="card-header">

        <h5 class="mb-0">
            Create Contra Entry
        </h5>

    </div>

    <div class="card-body">

        <form
            action="{{ route('company.contra.store') }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label>
                        Voucher No
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="{{ $contraNo }}"
                        readonly
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label>
                        Contra Date
                    </label>

                    <input
                        type="date"
                        name="contra_date"
                        class="form-control"
                        value="{{ old('contra_date',date('Y-m-d')) }}"
                        required
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label>
                        From Account
                    </label>

                    <select
                        name="from_account_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Account
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ old('from_account_id') == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                            ({{ number_format($account->current_balance,2) }})
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-6 mb-3">

                    <label>
                        To Account
                    </label>

                    <select
                        name="to_account_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Account
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ old('to_account_id') == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                            ({{ number_format($account->current_balance,2) }})
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-6 mb-3">

                    <label>
                        Amount
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="amount"
                        class="form-control"
                        value="{{ old('amount') }}"
                        required
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label>
                        Reference No
                    </label>

                    <input
                        type="text"
                        name="reference_no"
                        class="form-control"
                        value="{{ old('reference_no') }}"
                    >

                </div>

                <div class="col-md-12 mb-3">

                    <label>
                        Note
                    </label>

                    <textarea
                        name="note"
                        rows="3"
                        class="form-control"
                    >{{ old('note') }}</textarea>

                </div>

                <div class="col-md-12 mb-3">

                    <label>
                        Attachment
                    </label>

                    <input
                        type="file"
                        name="attachment"
                        class="form-control"
                    >

                    <small class="text-muted">
                        JPG, PNG, PDF only
                    </small>

                </div>

            </div>

            <div class="text-end">

                <a
                    href="{{ route('company.contra.index') }}"
                    class="btn btn-secondary"
                >
                    Back
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Contra
                </button>

            </div>

        </form>

    </div>

</div>

@endsection