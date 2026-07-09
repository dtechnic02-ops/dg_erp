@extends('company.layout')

@section('title','Contra Details')

@section('content')

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            Contra Details
        </h5>

        <div>

          

            <a
                href="{{ route('company.contra.index') }}"
                class="btn btn-secondary"
            >
                Back
            </a>

        </div>

    </div>
@include('company.partials.print-header')
    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-3">

                <strong>
                    Voucher No
                </strong>

                <br>

                {{ $contra->contra_no }}

            </div>

            <div class="col-md-6 mb-3">

                <strong>
                    Date
                </strong>

                <br>

                {{ $contra->contra_date }}

            </div>

            <div class="col-md-6 mb-3">

                <strong>
                    From Account
                </strong>

                <br>

                {{ $contra->fromAccount->account_name ?? '-' }}

            </div>

            <div class="col-md-6 mb-3">

                <strong>
                    To Account
                </strong>

                <br>

                {{ $contra->toAccount->account_name ?? '-' }}

            </div>

            <div class="col-md-6 mb-3">

                <strong>
                    Amount
                </strong>

                <br>

                {{ number_format($contra->amount,2) }}

            </div>

            <div class="col-md-6 mb-3">

                <strong>
                    Reference No
                </strong>

                <br>

                {{ $contra->reference_no ?? '-' }}

            </div>

            <div class="col-md-12 mb-3">

                <strong>
                    Note
                </strong>

                <br>

                {!! nl2br(e($contra->note ?? '-')) !!}

            </div>

            <div class="col-md-12 mb-3">

                <strong>
                    Attachment
                </strong>

                <br>

                @if($contra->attachment)

                    <a
                        href="{{ asset($contra->attachment) }}"
                        target="_blank"
                        class="btn btn-info btn-sm mt-2"
                    >
                        View Attachment
                    </a>

                @else

                    <span class="text-muted">
                        No Attachment
                    </span>

                @endif

            </div>

        </div>

    </div>

</div>
@include('company.partials.print-footer-portrait')
@endsection