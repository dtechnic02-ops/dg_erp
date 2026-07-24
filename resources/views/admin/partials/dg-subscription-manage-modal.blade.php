@php
    $modalId = 'dgSubscriptionManageModal' . $subscription->id;
    $companyName = $subscription->company->company_name ?? '-';
    $planName = $subscription->plan->name ?? 'Trial';
    $cycleName = $subscription->billingCycle->name ?? '-';
    $statusClass = match ($subscription->status) {
        'active' => 'success',
        'expired' => 'danger',
        'cancelled' => 'secondary',
        default => 'secondary',
    };
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">Manage Subscription — {{ $companyName }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Current Subscription</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Company</label>
                                <p class="mb-0 fw-semibold">{{ $companyName }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Plan</label>
                                <p class="mb-0 fw-semibold">{{ $planName }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Billing Cycle</label>
                                <p class="mb-0 fw-semibold">{{ $cycleName }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Start Date</label>
                                <p class="mb-0 fw-semibold">{{ $subscription->start_date?->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Expiry Date</label>
                                <p class="mb-0 fw-semibold">{{ $subscription->expiry_date?->format('d M Y') ?? 'Lifetime' }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Staff Limit</label>
                                <p class="mb-0 fw-semibold">{{ $subscription->staff_limit }}</p>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Status</label>
                                <p class="mb-0">
                                    <span class="dg-badge dg-badge-status dg-badge-{{ $statusClass }}">{{ ucfirst($subscription->status) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Assign Free Trial</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.free-trial', $subscription->company_id) }}" class="dg-form">
                            @csrf
                            <p class="text-muted small mb-3">Assign a promotional free trial to this company. Existing active subscriptions will be superseded.</p>
                            <button type="submit" class="btn btn-outline-info dg-btn">Assign Free Trial</button>
                        </form>
                    </div>
                </article>

                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Renew Subscription</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.renew', $subscription->company_id) }}" class="dg-form">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6 col-lg-4">
                                    <label for="{{ $modalId }}_renew_cycle" class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" id="{{ $modalId }}_renew_cycle" class="form-select dg-select" required>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected($subscription->billing_cycle_id == $cycle->id)>{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <button type="submit" class="btn btn-success dg-btn">Renew Subscription</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Upgrade Plan</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.upgrade', $subscription->company_id) }}" class="dg-form">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6 col-lg-4">
                                    <label for="{{ $modalId }}_upgrade_plan" class="form-label">Plan</label>
                                    <select name="subscription_plan_id" id="{{ $modalId }}_upgrade_plan" class="form-select dg-select" required>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected($subscription->subscription_plan_id == $plan->id)>{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="{{ $modalId }}_upgrade_cycle" class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" id="{{ $modalId }}_upgrade_cycle" class="form-select dg-select" required>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected($subscription->billing_cycle_id == $cycle->id)>{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <button type="submit" class="btn btn-primary dg-btn">Upgrade Plan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Downgrade Plan</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.downgrade', $subscription->company_id) }}" class="dg-form">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6 col-lg-4">
                                    <label for="{{ $modalId }}_downgrade_plan" class="form-label">Plan</label>
                                    <select name="subscription_plan_id" id="{{ $modalId }}_downgrade_plan" class="form-select dg-select" required>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected($subscription->subscription_plan_id == $plan->id)>{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="{{ $modalId }}_downgrade_cycle" class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" id="{{ $modalId }}_downgrade_cycle" class="form-select dg-select" required>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected($subscription->billing_cycle_id == $cycle->id)>{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <button type="submit" class="btn btn-warning dg-btn">Downgrade Plan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="card dg-card mb-3">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Expire Subscription</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.expire', $subscription->company_id) }}" class="dg-form">
                            @csrf
                            <p class="text-muted small mb-3">Mark this company's subscription as expired and block ERP access.</p>
                            <button type="submit" class="btn btn-outline-danger dg-btn">Expire Subscription</button>
                        </form>
                    </div>
                </article>

                @if ($subscription->status !== 'cancelled')
                    <article class="card dg-card mb-0">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Cancel Subscription</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription->id) }}" class="dg-form">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-12">
                                        <label for="{{ $modalId }}_cancel_reason" class="form-label">Cancel Reason</label>
                                        <textarea name="cancel_reason" id="{{ $modalId }}_cancel_reason" class="form-control dg-input" rows="3" required maxlength="500" placeholder="Enter cancellation reason"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-danger dg-btn">Cancel Subscription</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </article>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
