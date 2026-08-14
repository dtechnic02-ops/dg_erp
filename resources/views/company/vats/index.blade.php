@extends('company.layout')

@section('content')

<div class="dg-page">
    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <div class="flex-fill">
                    <h1 class="h4 mb-0">VAT &amp; Tax Management</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#addVatModal">Add VAT</button>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">
            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">VAT List</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">VAT Name</th>
                                        <th scope="col">Rate</th>
                                        <th scope="col">Default</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($vats as $key => $vat)
                                        <tr class="dg-row">
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $vat->name }}</td>
                                            <td>{{ $vat->rate }}%</td>
                                            <td>{{ $vat->is_default ? 'Yes' : 'No' }}</td>
                                            <td>
                                                <form action="{{ route('company.vats.delete', $vat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this VAT?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger dg-btn">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="5" class="text-center">No VAT Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

<div class="modal fade" id="addVatModal" tabindex="-1" aria-labelledby="addVatLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('company.vats.store') }}" method="POST" data-add-vat-form>
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="addVatLabel">Add VAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="vat_name" class="form-label">VAT Name</label>
                        <input type="text" name="name" id="vat_name" value="" placeholder="Example: VAT 13%" class="form-control dg-input" required>
                    </div>

                    <div class="mb-3">
                        <label for="vat_rate" class="form-label">Rate (%)</label>
                        <input type="number" step="0.01" name="rate" id="vat_rate" value="" class="form-control dg-input" required>
                    </div>

                    <div>
                        <label for="vat_is_default" class="form-label">Default VAT</label>
                        <select name="is_default" id="vat_is_default" class="form-select dg-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save VAT</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('addVatModal');

            modal?.addEventListener('show.bs.modal', function () {
                modal.querySelector('[data-add-vat-form]')?.reset();
            });
        });
    </script>
@endpush

@endsection
