@php
    $formMode = $mode ?? 'create';
    $isEdit = $formMode === 'edit';
    $displayAccountNo = $isEdit ? ($party->account_no ?? null) : ($accountNo ?? null);
@endphp

<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Party Account Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                @if ($displayAccountNo)
                    <div class="col-lg-4 col-md-6 col-12">
                        <label for="account_no_display" class="form-label dg-label">
                            Account No
                        </label>

                        <input
                            type="text"
                            id="account_no_display"
                            class="form-control dg-input"
                            value="{{ $displayAccountNo }}"
                            readonly>
                    </div>
                @endif

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ old('name', $isEdit ? $party->name : '') }}"
                        required>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="phone" class="form-label dg-label">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        class="form-control dg-input"
                        value="{{ old('phone', $isEdit ? $party->phone : '') }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="type" class="form-label dg-label">
                        Type
                    </label>

                    <select
                        name="type"
                        id="type"
                        class="form-select dg-select"
                        required>

                        @foreach (['bank', 'person', 'customer', 'supplier', 'company', 'other'] as $partyType)
                            <option
                                value="{{ $partyType }}"
                                {{ old('type', $isEdit ? $party->type : '') === $partyType ? 'selected' : '' }}>
                                {{ ucfirst($partyType) }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="status" class="form-label dg-label">
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="form-select dg-select">

                        <option
                            value="1"
                            {{ (string) old('status', $isEdit ? $party->status : 1) === '1' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option
                            value="0"
                            {{ (string) old('status', $isEdit ? $party->status : 1) === '0' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="due_date" class="form-label dg-label">
                        Due Date
                    </label>

                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        class="form-control dg-input"
                        value="{{ old('due_date', $isEdit && $party->due_date ? $party->due_date->format('Y-m-d') : '') }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="photo" class="form-label dg-label">
                        Photo
                    </label>

                    <input
                        type="file"
                        name="photo"
                        id="photo"
                        accept=".jpg,.jpeg,.png"
                        class="form-control dg-input">
                </div>

                @if ($isEdit)
                    <div class="col-lg-4 col-md-6 col-12">
                        <span class="form-label dg-label">
                            Photo Preview
                        </span>

                        @if (!empty($party->photo))
                            <div>
                                <img
                                    src="{{ asset($party->photo) }}"
                                    alt="{{ $party->name }} photo"
                                    width="60"
                                    height="60"
                                    class="rounded border">
                            </div>
                        @endif
                    </div>
                @endif

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="document" class="form-label dg-label">
                        PDF Document
                    </label>

                    <input
                        type="file"
                        name="document"
                        id="document"
                        accept=".pdf"
                        class="form-control dg-input">
                </div>

                @if ($isEdit)
                    <div class="col-lg-4 col-md-6 col-12">
                        <span class="form-label dg-label">
                            Document
                        </span>

                        @if (!empty($party->document))
                            <div>
                                <a
                                    href="{{ asset($party->document) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-secondary dg-btn">
                                    View PDF
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="address" class="form-label dg-label">
                        Address
                    </label>

                    <textarea
                        name="address"
                        id="address"
                        rows="2"
                        class="form-control dg-input">{{ old('address', $isEdit ? $party->address : '') }}</textarea>
                </div>

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="note" class="form-label dg-label">
                        Note
                    </label>

                    <textarea
                        name="note"
                        id="note"
                        rows="2"
                        class="form-control dg-input">{{ old('note', $isEdit ? $party->note : '') }}</textarea>
                </div>

            </div>
        </div>
    </div>
</div>
