@extends('company.layout')

@section('title','Transaction Details')

@section('content')

<div class="card">

    <div class="card-header">

        <h5 class="mb-0">
            Transaction Details
        </h5>

    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-3">
                <strong>
                    Transaction Date :
                </strong>
                <br>
                {{ $transaction->transaction_date }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Voucher No :
                </strong>
                <br>
                {{ $transaction->voucher_no }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Account :
                </strong>
                <br>
                {{ $transaction->account->account_name ?? '' }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Reference Type :
                </strong>
                <br>
                {{ $transaction->reference_type }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Reference ID :
                </strong>
                <br>
                {{ $transaction->reference_id }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Debit :
                </strong>
                <br>
                {{ number_format($transaction->debit,2) }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Credit :
                </strong>
                <br>
                {{ number_format($transaction->credit,2) }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Balance :
                </strong>
                <br>
                {{ number_format($transaction->balance,2) }}
            </div>

            <div class="col-md-12 mb-3">
                <strong>
                    Description :
                </strong>
                <br>

                {!! nl2br(e($transaction->description)) !!}
            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Status :
                </strong>
                <br>

                @if($transaction->status==1)

                    <span class="badge bg-success">
                        Active
                    </span>

                @else

                    <span class="badge bg-danger">
                        Inactive
                    </span>

                @endif

            </div>

            <div class="col-md-6 mb-3">
                <strong>
                    Created At :
                </strong>
                <br>
                {{ $transaction->created_at }}
            </div>

        </div>

    </div>

    <div class="card-footer">

        <a href="{{ route('company.account-transaction.index') }}"
           class="btn btn-secondary">

            Back

        </a>

    </div>

</div>

@endsection