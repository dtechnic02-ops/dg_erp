<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}">
                @csrf

                @if (!empty($entityId))
                    <input type="hidden" name="cancel_entity_id" value="{{ $entityId }}">
                @endif

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $modalTitle }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="{{ $modalId }}_cancel_date" class="form-label">Cancel Date <span class="text-danger">*</span></label>
                        <input type="date" name="cancel_date" id="{{ $modalId }}_cancel_date" class="form-control dg-input" value="{{ old('cancel_date', date('Y-m-d')) }}" required>
                        @error('cancel_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="{{ $modalId }}_cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="{{ $modalId }}_cancel_reason" class="form-control dg-input" rows="4" required>{{ old('cancel_reason') }}</textarea>
                        @error('cancel_reason')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger dg-btn">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
