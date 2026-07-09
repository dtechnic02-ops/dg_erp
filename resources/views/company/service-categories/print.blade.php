<!DOCTYPE html>
<html>
<head>

    <title>Service Categories</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>

        body {
            font-size: 14px;
        }

        @media print {

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3>Service Categories Report</h3>
            <small>
                Print Date: {{ now()->format('d M Y h:i A') }}
            </small>
        </div>

        <button onclick="window.print()"
                class="btn btn-dark no-print">
            Print
        </button>
    </div>

    <table class="table table-bordered">

        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            @foreach($categories as $key => $category)

                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ ucfirst($category->status) }}</td>
                </tr>

            @endforeach
        </tbody>
    </table>
</div>

</body>
</html>