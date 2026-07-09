
@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="page-title">


supplier-ledger

<div class="row">

    <div class="col-md-4">

        <div class="card">

            <div class="card-body">

                <h6>Total Debit</h6>

                <h4>

                    {{ number_format(
                        $totalDebit,
                        2
                    ) }}

                </h4>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card">

            <div class="card-body">

                <h6>Total Credit</h6>

                <h4>

                    {{ number_format(
                        $totalCredit,
                        2
                    ) }}

                </h4>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card">

            <div class="card-body">

                <h6>Current Balance</h6>

                <h4>

                    {{ number_format(
                        $currentBalance,
                        2
                    ) }}

                </h4>

            </div>

        </div>

    </div>

</div>
<table class="table table-bordered table-striped">

    <thead>

        <tr>

            <th>Date</th>

            <th>Voucher</th>

            <th>Type</th>

            <th class="text-end">
                Debit
            </th>

            <th class="text-end">
                Credit
            </th>

            <th class="text-end">
                Balance
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse($ledger as $row)

        <tr>

            <td>
                {{ $row['date'] }}
            </td>

            <td>
                {{ $row['voucher'] }}
            </td>

            <td>
                {{ $row['type'] }}
            </td>

            <td class="text-end">

                {{ number_format(
                    $row['debit'],
                    2
                ) }}

            </td>

            <td class="text-end">

                {{ number_format(
                    $row['credit'],
                    2
                ) }}

            </td>

            <td class="text-end fw-bold">

                {{ number_format(
                    $row['balance'],
                    2
                ) }}

            </td>

        </tr>

        @empty

        <tr>

            <td colspan="6"
                class="text-center">

                No Ledger Found.

            </td>

        </tr>

        @endforelse

    </tbody>

</table>