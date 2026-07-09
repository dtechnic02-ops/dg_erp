@extends('company.layout')

@section('content')

<div class="card">

```
<div class="card-header d-flex justify-content-between align-items-center">

    <h5 class="mb-0">

        Edit Contra

    </h5>

    <a
        href="{{ route('company.contra.index') }}"
        class="btn btn-secondary"
    >

        Back

    </a>

</div>

<div class="card-body">

    <form
        method="POST"
        action="{{ route('company.contra.update',$contra->id) }}"
        enctype="multipart/form-data"
    >

        @csrf

        <div class="row">

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    Voucher No

                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $contra->voucher_no }}"
                    readonly
                >

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    Date

                </label>

                <input
                    type="date"
                    name="contra_date"
                    class="form-control"
                    value="{{ old('contra_date',$contra->contra_date) }}"
                    required
                >

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

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
                        {{ $contra->from_account_id == $account->id ? 'selected' : '' }}
                    >

                        {{ $account->account_name }}

                    </option>

                    @endforeach

                </select>

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

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
                        {{ $contra->to_account_id == $account->id ? 'selected' : '' }}
                    >

                        {{ $account->account_name }}

                    </option>

                    @endforeach

                </select>

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    Amount

                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="amount"
                    class="form-control"
                    value="{{ old('amount',$contra->amount) }}"
                    required
                >

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    Reference No

                </label>

                <input
                    type="text"
                    name="reference_no"
                    class="form-control"
                    value="{{ old('reference_no',$contra->reference_no) }}"
                >

            </div>

            <div class="col-md-12 mb-3">

                <label class="form-label">

                    Note

                </label>

                <textarea
                    name="note"
                    class="form-control"
                    rows="3"
                >{{ old('note',$contra->note) }}</textarea>

            </div>

            <div class="col-md-12 mb-3">

                <label class="form-label">

                    Attachment

                </label>

                <input
                    type="file"
                    name="attachment"
                    class="form-control"
                >

                @if($contra->attachment)

                <div class="mt-2">

                    <a
                        href="{{ asset($contra->attachment) }}"
                        target="_blank"
                    >

                        View Current Attachment

                    </a>

                </div>

                @endif

            </div>

        </div>

        <button
            type="submit"
            class="btn btn-primary"
        >

            Update Contra

        </button>

    </form>

</div>
```

</div>

@endsection
