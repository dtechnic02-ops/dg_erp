@extends('company.layout')

@section('content')

<div class="page-title">

    <h3>

        🔧 System Maintenance

    </h3>

</div>

<div class="row">

    <div class="col-md-4 mb-3">

        <div class="card">

            <div class="card-body">

                <h5>

                    Account Ledger

                </h5>

                <form method="POST"
                      action="{{ route('company.maintenance.recalculate.ledger') }}">

                    @csrf

                    <button
                        class="erp-btn btn-blue"
                        onclick="return confirm('Recalculate Account Ledger?')">

                        Recalculate Ledger

                    </button>

                </form>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-3">

        <div class="card">

            <div class="card-body">

                <h5>

                    Stock

                </h5>

                <form method="POST"
                      action="{{ route('company.maintenance.recalculate.stock') }}">

                    @csrf

                    <button
                        class="erp-btn btn-green"
                        onclick="return confirm('Recalculate Stock?')">

                        Recalculate Stock

                    </button>

                </form>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-3">

        <div class="card">

            <div class="card-body">

                <h5>

                    Purchase Invoice

                </h5>

                <form method="POST"
                      action="{{ route('company.maintenance.recalculate.purchase.invoices') }}">

                    @csrf

                    <button
                        class="erp-btn btn-warning"
                        onclick="return confirm('Recalculate Purchase Invoices?')">

                        Repair Purchase Invoices

                    </button>

                </form>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-3">

        <div class="card">

            <div class="card-body">

                <h5>

                    Customer Statement

                </h5>

                <form method="POST"
                      action="{{ route('company.maintenance.recalculate.customer.statement') }}">

                    @csrf

                    <button
                        class="erp-btn btn-blue"
                        onclick="return confirm('Recalculate Customer Statement?')">

                        Recalculate Customer Statement

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection