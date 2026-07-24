@if ($salarySheet->isPaid())
    <span class="badge bg-success">Paid</span>
@elseif ($salarySheet->isPartial())
    <span class="badge bg-info text-dark">Partial</span>
@elseif ($salarySheet->isCancelled())
    <span class="badge bg-secondary">Cancelled</span>
@else
    <span class="badge bg-warning text-dark">Unpaid</span>
@endif
