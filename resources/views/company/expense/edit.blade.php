@extends('company.layout')

@section('content')

<div class="container-fluid">

```
<div class="card">

    <div class="card-header">

        <h5 class="mb-0">
            Edit Expense
        </h5>

    </div>

    <div class="card-body">

        <form
            action="{{ route('company.expense.update',$expense->id) }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Category
                    </label>

                    <select
                        name="expense_category_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            {{ $expense->expense_category_id == $category->id ? 'selected' : '' }}
                        >
                            {{ $category->name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Account
                    </label>

                    <select
                        name="account_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Account
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ $expense->account_id == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Expense Date
                    </label>

                    <input
                        type="date"
                        name="expense_date"
                        class="form-control"
                        value="{{ $expense->expense_date }}"
                        required
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Amount
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="amount"
                        class="form-control"
                        value="{{ $expense->amount }}"
                        required
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Reference No
                    </label>

                    <input
                        type="text"
                        name="reference_no"
                        class="form-control"
                        value="{{ $expense->reference_no }}"
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Attachment
                    </label>

                    <input
                        type="file"
                        name="attachment"
                        class="form-control"
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
                    >{{ $expense->note }}</textarea>

                </div>

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Update Expense
            </button>

            <a
                href="{{ route('company.expense.index') }}"
                class="btn btn-secondary"
            >
                Back
            </a>

        </form>

    </div>

</div>
```

</div>

@endsection
