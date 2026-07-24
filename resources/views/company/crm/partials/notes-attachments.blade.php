@php
    $entityType = $entityType ?? 'lead';
    $entityId = $entityId ?? 0;
@endphp

<section class="dg-section">
    <article class="card dg-card">
        <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h6 mb-0">Notes</h2>
        </header>
        <div class="card-body dg-card-body">
            @if (userCan('create_crm_note'))
                <form method="POST" action="{{ route('company.crm-notes.store') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="entity_type" value="{{ $entityType }}">
                    <input type="hidden" name="entity_id" value="{{ $entityId }}">
                    <div class="mb-2">
                        <label for="note_{{ $entityType }}_{{ $entityId }}" class="form-label">Add Note</label>
                        <textarea name="note" id="note_{{ $entityType }}_{{ $entityId }}" class="form-control dg-input" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary dg-btn">Save Note</button>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table dg-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Note</th>
                            <th>By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notes as $note)
                            <tr>
                                <td>{{ $note->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td>{{ $note->note }}</td>
                                <td>{{ $note->creator->name ?? '-' }}</td>
                                <td>
                                    @if (userCan('archive_crm_note'))
                                        <form method="POST" action="{{ route('company.crm-notes.archive', $note->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="archive_reason" value="Archived from detail view">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary dg-btn" onclick="return confirm('Archive this note?')">Archive</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No notes recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>

<section class="dg-section">
    <article class="card dg-card">
        <header class="card-header dg-card-header">
            <h2 class="h6 mb-0">Attachments</h2>
        </header>
        <div class="card-body dg-card-body">
            @if (userCan('create_crm_attachment'))
                <form method="POST" action="{{ route('company.crm-attachments.store') }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <input type="hidden" name="entity_type" value="{{ $entityType }}">
                    <input type="hidden" name="entity_id" value="{{ $entityId }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="file_{{ $entityType }}_{{ $entityId }}" class="form-label">File</label>
                            <input type="file" name="file" id="file_{{ $entityType }}_{{ $entityId }}" class="form-control dg-input" required>
                        </div>
                        <div class="col-md-3">
                            <label for="document_type_{{ $entityType }}_{{ $entityId }}" class="form-label">Type</label>
                            <input type="text" name="document_type" id="document_type_{{ $entityType }}_{{ $entityId }}" class="form-control dg-input" value="attachment">
                        </div>
                        <div class="col-md-3">
                            <label for="remarks_{{ $entityType }}_{{ $entityId }}" class="form-label">Remarks</label>
                            <input type="text" name="remarks" id="remarks_{{ $entityType }}_{{ $entityId }}" class="form-control dg-input">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary dg-btn w-100">Upload</button>
                        </div>
                    </div>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table dg-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attachments as $attachment)
                            <tr>
                                <td>{{ $attachment->original_name }}</td>
                                <td>{{ $attachment->document_type }}</td>
                                <td>{{ $attachment->file_size ? number_format($attachment->file_size / 1024, 2) . ' KB' : '-' }}</td>
                                <td>{{ $attachment->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td class="text-nowrap">
                                    @if (userCan('view_crm_attachment'))
                                        <a href="{{ route('company.crm-attachments.preview', $attachment->id) }}" class="btn btn-sm btn-outline-info dg-btn" target="_blank">Preview</a>
                                        <a href="{{ route('company.crm-attachments.download', $attachment->id) }}" class="btn btn-sm btn-outline-secondary dg-btn">Download</a>
                                    @endif
                                    @if (userCan('archive_crm_attachment'))
                                        <form method="POST" action="{{ route('company.crm-attachments.archive', $attachment->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="archive_reason" value="Archived from detail view">
                                            <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Archive this attachment?')">Archive</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No attachments uploaded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>
