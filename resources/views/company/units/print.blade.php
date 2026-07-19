@php
    $company = auth()->user()->company;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit List Print</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 4px 6px;
            vertical-align: middle;
        }

        .no-print {
            display: none;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container-fluid">

        <div class="row align-items-center border-bottom pb-2 mb-2">
            <div class="col-2">
                @if ($company && $company->logo_path)
                    <img
                        src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                        alt="Company Logo"
                        width="70"
                        height="70">
                @endif
            </div>

            <div class="col-10 text-center">
                <h4 class="mb-0">{{ $company->company_name ?? '-' }}</h4>
                <div>{{ $company->address ?? '-' }}</div>
                <div>{{ $company->mobile ?? '-' }} {{ $company->email ? '| ' . $company->email : '' }}</div>
            </div>
        </div>

        <div class="text-center mb-2">
            <h5 class="mb-0">UNIT LIST</h5>
            @if (request('search'))
                <div>Filtered by: "{{ request('search') }}"</div>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Unit Name</th>
                    <th>Short Name</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($units as $index => $u)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->short_name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No Units Found</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="2" class="text-end"><strong>Total Units</strong></td>
                    <td class="text-center"><strong>{{ $totalUnits }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-3">
            <div class="col-4">Generated: {{ now()->format('Y-m-d H:i') }}</div>
            <div class="col-4 text-center">Printed By: {{ auth()->user()->name ?? '-' }}</div>
            <div class="col-4 text-end">Page 1</div>
        </div>

    </div>

</body>
</html>
