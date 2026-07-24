@extends('company.layout')



@section('title', 'Create Journal Entry')



@section('content')



<div class="dg-page dg-journal-entry">



    <header class="dg-toolbar">

        <div class="container-fluid">

            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">

                    <h1 class="h4 mb-0">Create Journal Entry</h1>

                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">

                    <nav class="btn-group" aria-label="Journal create toolbar">

                        <a href="{{ route('company.journal.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>

                    </nav>

                </div>

            </div>

        </div>

    </header>



    <main class="dg-container">

        <div class="container-fluid">



            @if ($errors->any())

                <div class="alert alert-danger dg-alert" role="alert">

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif



            @if (session('error'))

                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>

            @endif



            <form method="POST" action="{{ route('company.journal.store') }}" enctype="multipart/form-data">

                @csrf



                <section class="dg-section">

                    <article class="card dg-card">

                        <header class="card-header dg-card-header">

                            <h2 class="h6 mb-0">Journal Information</h2>

                        </header>

                        <div class="card-body dg-card-body">

                            <div class="row g-2 dg-journal-header-row">

                                <div class="col-md-3">

                                    <label for="financial_year" class="form-label">Financial Year</label>

                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $activeFy->name }}" readonly>

                                </div>

                                <div class="col-md-3">

                                    <label for="journal_date" class="form-label">Journal Date <span class="text-danger">*</span></label>

                                    <input type="date" name="journal_date" id="journal_date" class="form-control dg-input" value="{{ old('journal_date', date('Y-m-d')) }}" required>

                                </div>

                                <div class="col-md-3">

                                    <label for="reference_no" class="form-label">Reference No</label>

                                    <input type="text" name="reference_no" id="reference_no" class="form-control dg-input" value="{{ old('reference_no') }}" maxlength="100">

                                </div>

                                <div class="col-md-3">

                                    <label for="attachment" class="form-label">Attachment</label>

                                    <input type="file" name="attachment" id="attachment" class="form-control dg-input" accept=".jpg,.jpeg,.png,.pdf">

                                </div>

                                <div class="col-12">

                                    <label for="note" class="form-label">Narration <span class="text-danger">*</span></label>

                                    <textarea name="note" id="note" class="form-control dg-input" required>{{ old('note') }}</textarea>

                                </div>

                            </div>

                        </div>

                    </article>

                </section>



                <section class="dg-section">

                    <article class="card dg-card">

                        <header class="card-header dg-card-header">

                            <h2 class="h6 mb-0">Journal Details</h2>

                        </header>

                        <div class="card-body dg-card-body">

                            @includeIsolated('company.journal.partials.detail-grid', [
                                'chartAccounts' => $chartAccounts,
                                'customers'     => $customers,
                                'suppliers'     => $suppliers,
                                'employees'     => $employees,
                                'parties'       => $parties,
                            ])

                        </div>

                    </article>

                </section>



                <section class="dg-section">

                    <div class="dg-journal-footer">

                        <a href="{{ route('company.journal.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>

                        <button type="submit" id="journalSaveBtn" class="btn btn-primary dg-btn" disabled>Save Journal</button>

                    </div>

                </section>

            </form>



        </div>

    </main>

</div>



@endsection

