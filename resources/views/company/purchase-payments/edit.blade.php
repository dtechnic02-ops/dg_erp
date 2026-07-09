@extends('company.layout')

@section('content')

<div class="container-fluid">

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">
                Edit Purchase Payment
            </h4>

        </div>

        <div class="card-body">

            <form
                action="{{ route(
                    'company.purchase-payments.update',
                    $payment->id
                ) }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Payment No
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ $payment->payment_no }}"
                            readonly
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Amount
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ number_format(
                                $payment->amount,
                                2
                            ) }}"
                            readonly
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Payment Method
                        </label>

                        <input
                            type="text"
                            name="payment_method"
                            value="{{ old(
                                'payment_method',
                                $payment->payment_method
                            ) }}"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Reference No
                        </label>

                        <input
                            type="text"
                            name="reference_no"
                            value="{{ old(
                                'reference_no',
                                $payment->reference_no
                            ) }}"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Note
                        </label>

                        <textarea
                            name="note"
                            rows="3"
                            class="form-control"
                        >{{ old(
                            'note',
                            $payment->note
                        ) }}</textarea>

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Receipt File
                        </label>

                        <input
                            type="file"
                            name="receipt_file"
                            class="form-control"
                        >

                    </div>

                    @if($payment->receipt_file)

                    <div class="col-md-12 mb-3">

                        <a
                            href="{{ asset(
                                $payment->receipt_file
                            ) }}"
                            target="_blank"
                            class="btn btn-info"
                        >
                            View Current File
                        </a>

                    </div>

                    @endif

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Payment
                </button>

                <a
                    href="{{ route(
                        'company.purchase-payments.show',
                        $payment->id
                    ) }}"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </form>

        </div>

    </div>

</div>

@endsection