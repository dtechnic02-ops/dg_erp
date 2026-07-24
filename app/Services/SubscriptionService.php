<?php

namespace App\Services;

use App\Models\BillingCycle;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanBillingOption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    public function startRegisterTrial(Company $company, ?User $performedBy = null): CompanySubscription
    {
        return $this->withCompanySubscriptionLock($company, function (Company $lockedCompany) use ($performedBy) {
            $this->expireActiveSubscriptions($lockedCompany, $performedBy, 'Superseded by register trial');

            $startDate = now()->toDateString();
            $expiryDate = now()->addDays((int) config('subscription.register_trial_days'))->toDateString();

            $subscription = CompanySubscription::create([
                'company_id' => $lockedCompany->id,
                'subscription_type' => 'register_trial',
                'status' => 'active',
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'staff_limit' => (int) config('subscription.register_trial_staff_limit'),
                'hidden_modules' => null,
                'is_all_modules_enabled' => true,
                'activated_at' => now(),
                'created_by' => $performedBy?->id,
                'updated_by' => $performedBy?->id,
            ]);

            $this->syncCompanyFromSubscription($lockedCompany, $subscription, 'active');
            $this->recordHistory($subscription, 'register_trial_started', $performedBy, null, $subscription);

            return $subscription->fresh();
        }, $performedBy);
    }

    public function assignFreeTrial(Company $company, User $performedBy): CompanySubscription
    {
        return $this->withCompanySubscriptionLock($company, function (Company $lockedCompany) use ($performedBy) {
            $this->expireActiveSubscriptions($lockedCompany, $performedBy, 'Superseded by free trial');

            $startDate = now()->toDateString();
            $expiryDate = now()->addDays((int) config('subscription.free_trial_days'))->toDateString();

            $subscription = CompanySubscription::create([
                'company_id' => $lockedCompany->id,
                'subscription_type' => 'free_trial',
                'status' => 'active',
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'staff_limit' => (int) config('subscription.free_trial_staff_limit'),
                'hidden_modules' => null,
                'is_all_modules_enabled' => true,
                'activated_at' => now(),
                'approved_by' => $performedBy->id,
                'approved_at' => now(),
                'created_by' => $performedBy->id,
                'updated_by' => $performedBy->id,
            ]);

            $this->syncCompanyFromSubscription($lockedCompany, $subscription, 'active');
            $this->recordHistory($subscription, 'free_trial_assigned', $performedBy, null, $subscription);

            return $subscription->fresh();
        }, $performedBy);
    }

    public function syncBillingOptions(SubscriptionPlan $plan, array $options, User $user): void
    {
        DB::transaction(function () use ($plan, $options, $user) {
            foreach ($options as $cycleId => $option) {
                if (! isset($option['enabled'])) {
                    SubscriptionPlanBillingOption::where('subscription_plan_id', $plan->id)
                        ->where('billing_cycle_id', $cycleId)
                        ->update(['is_active' => false, 'updated_by' => $user->id]);

                    continue;
                }

                SubscriptionPlanBillingOption::updateOrCreate(
                    [
                        'subscription_plan_id' => $plan->id,
                        'billing_cycle_id' => $cycleId,
                    ],
                    [
                        'price' => $option['price'] ?? 0,
                        'currency_code' => $option['currency_code'] ?? 'NPR',
                        'is_active' => true,
                        'updated_by' => $user->id,
                    ]
                );
            }
        });
    }

    public function submitPayment(array $data, User $user): SubscriptionPayment
    {
        return DB::transaction(function () use ($data, $user) {
            $plan = SubscriptionPlan::active()->findOrFail($data['subscription_plan_id']);
            $cycle = BillingCycle::active()->findOrFail($data['billing_cycle_id']);

            $option = $plan->billingOptions()
                ->where('billing_cycle_id', $cycle->id)
                ->where('is_active', true)
                ->first();

            if (! $option) {
                throw new RuntimeException('Selected plan and billing cycle combination is not available.');
            }

            $current = $this->getCurrentSubscription($user->company);
            $actionType = $this->resolvePaymentActionType($current, $plan);

            $payment = SubscriptionPayment::create([
                'company_id' => $user->company_id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'action_type' => $actionType,
                'amount' => $option->price,
                'currency_code' => $option->currency_code,
                'payment_method' => $data['payment_method'] ?? 'manual',
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference_no' => $data['reference_no'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'pending',
                'target_subscription_id' => $current?->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->recordHistory(
                $current,
                'payment_submitted',
                $user,
                $payment,
                $current,
                notes: 'Payment submitted for ' . $actionType
            );

            return $payment;
        });
    }

    public function createManualPayment(array $data, User $user): SubscriptionPayment
    {
        return DB::transaction(function () use ($data, $user) {
            $company = Company::findOrFail($data['company_id']);
            $plan = SubscriptionPlan::active()->findOrFail($data['subscription_plan_id']);
            $cycle = BillingCycle::active()->findOrFail($data['billing_cycle_id']);

            $actionType = $data['action_type'] ?? 'assign';

            if (! in_array($actionType, ['assign', 'renew', 'upgrade', 'downgrade'], true)) {
                throw new RuntimeException('Invalid payment action type.');
            }

            $current = $this->getCurrentSubscription($company);

            if ($actionType === 'assign') {
                $actionType = $this->resolvePaymentActionType($current, $plan);
            }

            $amount = $data['amount'] ?? null;

            if ($amount === null) {
                $option = $plan->billingOptions()
                    ->where('billing_cycle_id', $cycle->id)
                    ->where('is_active', true)
                    ->first();

                if (! $option) {
                    throw new RuntimeException('Selected plan and billing cycle combination is not available.');
                }

                $amount = $option->price;
            }

            $payment = SubscriptionPayment::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'action_type' => $actionType,
                'amount' => $amount,
                'currency_code' => $data['currency_code'] ?? 'NPR',
                'payment_method' => $data['payment_method'] ?? 'manual',
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference_no' => $data['reference_no'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'pending',
                'target_subscription_id' => $current?->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->recordHistory(
                $current,
                'payment_submitted',
                $user,
                $payment,
                $current,
                notes: 'Manual payment recorded for ' . $actionType
            );

            return $payment;
        });
    }

    public function verifyPayment(SubscriptionPayment $payment, User $verifier): SubscriptionPayment
    {
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Only pending payments can be verified.');
        }

        return DB::transaction(function () use ($payment, $verifier) {
            $payment->update([
                'verified_at' => now(),
                'verified_by' => $verifier->id,
                'updated_by' => $verifier->id,
            ]);

            return $payment->fresh();
        });
    }

    public function approvePayment(SubscriptionPayment $payment, User $approver): CompanySubscription
    {
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Payment has already been processed.');
        }

        $company = Company::findOrFail($payment->company_id);

        return $this->withCompanySubscriptionLock($company, function (Company $lockedCompany) use ($payment, $approver) {
            $payment->refresh();

            if ($payment->status !== 'pending') {
                throw new RuntimeException('Payment has already been processed.');
            }

            $payment->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'verified_at' => $payment->verified_at ?? now(),
                'verified_by' => $payment->verified_by ?? $approver->id,
                'updated_by' => $approver->id,
            ]);

            $plan = SubscriptionPlan::findOrFail($payment->subscription_plan_id);
            $cycle = BillingCycle::findOrFail($payment->billing_cycle_id);
            $current = $this->getCurrentSubscription($lockedCompany);

            $subscription = match ($payment->action_type) {
                'renew' => $this->performRenewSubscription($lockedCompany, $current, $cycle, $payment, $approver),
                'upgrade' => $this->performUpgradePlan($lockedCompany, $current, $plan, $cycle, $payment, $approver),
                'downgrade' => $this->performDowngradePlan($lockedCompany, $current, $plan, $cycle, $payment, $approver),
                default => $this->assignPlan($lockedCompany, $plan, $cycle, $payment, $approver),
            };

            $payment->update(['company_subscription_id' => $subscription->id]);

            $this->recordHistory($subscription, 'payment_approved', $approver, $payment, $subscription);

            return $subscription->fresh();
        }, $approver);
    }

    public function rejectPayment(SubscriptionPayment $payment, User $rejecter, string $reason): SubscriptionPayment
    {
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Payment has already been processed.');
        }

        return DB::transaction(function () use ($payment, $rejecter, $reason) {
            $payment->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $rejecter->id,
                'rejection_reason' => $reason,
                'updated_by' => $rejecter->id,
            ]);

            $current = $this->getCurrentSubscription($payment->company);
            $this->recordHistory($current, 'payment_rejected', $rejecter, $payment, $current, notes: $reason);

            return $payment->fresh();
        });
    }

    public function assignPlan(
        Company $company,
        SubscriptionPlan $plan,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        $before = $this->getActiveSubscription($company);
        $this->expireActiveSubscriptions($company, $approver, 'Superseded by paid plan assignment');

        $expiryDate = $this->calculateRenewalExpiry(null, $cycle, true);

        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'subscription_type' => 'paid',
            'subscription_plan_id' => $plan->id,
            'billing_cycle_id' => $cycle->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'expiry_date' => $expiryDate?->toDateString(),
            'staff_limit' => $plan->staff_limit,
            'hidden_modules' => $plan->hidden_modules,
            'is_all_modules_enabled' => empty($plan->hidden_modules),
            'previous_subscription_id' => $before?->id,
            'activated_at' => now(),
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'created_by' => $approver->id,
            'updated_by' => $approver->id,
        ]);

        $this->syncCompanyFromSubscription($company, $subscription, 'active');
        $this->recordHistory($subscription, 'plan_assigned', $approver, $payment, $subscription, $before);

        return $subscription;
    }

    public function renewSubscription(
        Company $company,
        ?CompanySubscription $active,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        return $this->withCompanySubscriptionLock(
            $company,
            fn (Company $lockedCompany) => $this->performRenewSubscription(
                $lockedCompany,
                $active ? CompanySubscription::forCompany($lockedCompany->id)->where('id', $active->id)->first() : null,
                $cycle,
                $payment,
                $approver
            ),
            $approver
        );
    }

    protected function performRenewSubscription(
        Company $company,
        ?CompanySubscription $active,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        if (! $active) {
            if (! $payment) {
                throw new RuntimeException('Cannot renew without an existing subscription or payment record.');
            }

            return $this->assignPlan($company, $payment->plan, $cycle, $payment, $approver);
        }

        $before = clone $active;
        $isExpired = $active->status === 'expired'
            || ($active->expiry_date && Carbon::parse($active->expiry_date)->lt(now()->startOfDay()));

        $expiryDate = $this->calculateRenewalExpiry(
            $active->expiry_date ? Carbon::parse($active->expiry_date) : null,
            $cycle,
            $isExpired
        );

        $active->update([
            'status' => 'active',
            'expiry_date' => $expiryDate?->toDateString(),
            'billing_cycle_id' => $cycle->id,
            'updated_by' => $approver->id,
        ]);

        $this->syncCompanyFromSubscription($company, $active, 'active');
        $this->recordHistory($active, 'renewed', $approver, $payment, $active, $before);

        return $active->fresh();
    }

    public function upgradePlan(
        Company $company,
        ?CompanySubscription $active,
        SubscriptionPlan $plan,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        return $this->withCompanySubscriptionLock(
            $company,
            fn (Company $lockedCompany) => $this->performUpgradePlan(
                $lockedCompany,
                $active ? CompanySubscription::forCompany($lockedCompany->id)->where('id', $active->id)->first() : null,
                $plan,
                $cycle,
                $payment,
                $approver
            ),
            $approver
        );
    }

    protected function performUpgradePlan(
        Company $company,
        ?CompanySubscription $active,
        SubscriptionPlan $plan,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        if (! $active) {
            if (! $payment) {
                throw new RuntimeException('Cannot upgrade without an existing subscription or payment record.');
            }

            return $this->assignPlan($company, $plan, $cycle, $payment, $approver);
        }

        $before = clone $active;

        $active->update([
            'subscription_type' => 'paid',
            'subscription_plan_id' => $plan->id,
            'billing_cycle_id' => $cycle->id,
            'staff_limit' => $plan->staff_limit,
            'hidden_modules' => $plan->hidden_modules,
            'is_all_modules_enabled' => empty($plan->hidden_modules),
            'status' => 'active',
            'updated_by' => $approver->id,
        ]);

        $this->syncCompanyFromSubscription($company, $active, 'active');
        $this->recordHistory($active, 'upgraded', $approver, $payment, $active, $before);

        return $active->fresh();
    }

    public function downgradePlan(
        Company $company,
        ?CompanySubscription $active,
        SubscriptionPlan $plan,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        return $this->withCompanySubscriptionLock(
            $company,
            fn (Company $lockedCompany) => $this->performDowngradePlan(
                $lockedCompany,
                $active ? CompanySubscription::forCompany($lockedCompany->id)->where('id', $active->id)->first() : null,
                $plan,
                $cycle,
                $payment,
                $approver
            ),
            $approver
        );
    }

    protected function performDowngradePlan(
        Company $company,
        ?CompanySubscription $active,
        SubscriptionPlan $plan,
        BillingCycle $cycle,
        ?SubscriptionPayment $payment,
        User $approver
    ): CompanySubscription {
        if (! $active) {
            if (! $payment) {
                throw new RuntimeException('Cannot downgrade without an existing subscription or payment record.');
            }

            return $this->assignPlan($company, $plan, $cycle, $payment, $approver);
        }

        $before = clone $active;

        $active->update([
            'subscription_type' => 'paid',
            'subscription_plan_id' => $plan->id,
            'billing_cycle_id' => $cycle->id,
            'staff_limit' => $plan->staff_limit,
            'hidden_modules' => $plan->hidden_modules,
            'is_all_modules_enabled' => empty($plan->hidden_modules),
            'status' => 'active',
            'updated_by' => $approver->id,
        ]);

        $this->syncCompanyFromSubscription($company, $active, 'active');
        $this->recordHistory($active, 'downgraded', $approver, $payment, $active, $before);

        return $active->fresh();
    }

    public function expireSubscription(Company $company, ?User $performedBy = null): void
    {
        $this->withCompanySubscriptionLock($company, function (Company $lockedCompany) use ($performedBy) {
            $active = $this->getActiveSubscription($lockedCompany, includeExpired: true);

            if (! $active || $active->status === 'expired') {
                if ($lockedCompany->status !== 'expired') {
                    $lockedCompany->update(['status' => 'expired']);
                }

                return null;
            }

            if ($active->expiry_date && Carbon::parse($active->expiry_date)->isFuture()) {
                return null;
            }

            $before = clone $active;

            $active->update([
                'status' => 'expired',
                'expired_at' => now(),
                'updated_by' => $performedBy?->id,
            ]);

            $lockedCompany->update(['status' => 'expired']);
            $this->recordHistory($active, 'expired', $performedBy, null, $active, $before);

            return null;
        }, $performedBy);
    }

    public function cancelSubscription(CompanySubscription $subscription, User $performer, string $reason): CompanySubscription
    {
        $company = $subscription->company;

        return $this->withCompanySubscriptionLock($company, function (Company $lockedCompany) use ($subscription, $performer, $reason) {
            $subscription = CompanySubscription::forCompany($lockedCompany->id)
                ->where('id', $subscription->id)
                ->firstOrFail();

            $before = clone $subscription;

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $performer->id,
                'cancel_reason' => $reason,
                'updated_by' => $performer->id,
            ]);

            if ($this->getActiveSubscription($lockedCompany)?->id === $subscription->id) {
                $lockedCompany->update(['status' => 'expired']);
            }

            $this->recordHistory($subscription, 'cancelled', $performer, null, $subscription, $before, $reason);

            return $subscription->fresh();
        }, $performer);
    }

    public function getActiveSubscription(Company $company, bool $includeExpired = false): ?CompanySubscription
    {
        return CompanySubscription::forCompany($company->id)
            ->when(! $includeExpired, fn ($q) => $q->active())
            ->orderByDesc('id')
            ->first();
    }

    public function getCurrentSubscription(Company $company): ?CompanySubscription
    {
        return CompanySubscription::forCompany($company->id)
            ->whereIn('status', ['active', 'expired'])
            ->orderByDesc('id')
            ->first();
    }

    public function getEffectiveStaffLimit(Company $company): int
    {
        return (int) ($this->getActiveSubscription($company)?->staff_limit ?? 0);
    }

    public function getHiddenModules(Company $company): array
    {
        $subscription = $this->getActiveSubscription($company);

        if (! $subscription || $subscription->is_all_modules_enabled) {
            return [];
        }

        return $subscription->hidden_modules ?? [];
    }

    public function canCreateStaff(Company $company): bool
    {
        $limit = $this->getEffectiveStaffLimit($company);

        if ($limit <= 0) {
            return false;
        }

        $currentStaff = User::where('company_id', $company->id)
            ->where('role_id', 3)
            ->count();

        return $currentStaff < $limit;
    }

    public function canAccessModule(Company $company, string $module): bool
    {
        if (! $this->isSubscriptionOperational($company)) {
            return false;
        }

        return ! in_array($module, $this->getHiddenModules($company), true);
    }

    public function assertModuleAccess(Company $company, string $module): void
    {
        if (! $this->canAccessModule($company, $module)) {
            abort(403, 'This module is not available on your current subscription plan.');
        }
    }

    public function isSubscriptionOperational(Company $company): bool
    {
        if ($company->status === 'blocked') {
            return false;
        }

        $subscription = $this->getActiveSubscription($company);

        if (! $subscription) {
            return false;
        }

        if ($subscription->expiry_date && Carbon::parse($subscription->expiry_date)->lt(now()->startOfDay())) {
            return false;
        }

        return true;
    }

    public function calculateRenewalExpiry(?Carbon $currentExpiry, BillingCycle $cycle, bool $isExpired): ?Carbon
    {
        if ($cycle->is_lifetime) {
            return null;
        }

        $days = (int) $cycle->duration_days;

        if ($isExpired || ! $currentExpiry || $currentExpiry->lt(now()->startOfDay())) {
            return now()->addDays($days);
        }

        return $currentExpiry->copy()->addDays($days);
    }

    public function resolveRouteModule(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (config('subscription.route_module_map', []) as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        return null;
    }

    protected function resolvePaymentActionType(?CompanySubscription $active, SubscriptionPlan $targetPlan): string
    {
        if (! $active || in_array($active->subscription_type, ['register_trial', 'free_trial'], true)) {
            return 'assign';
        }

        if (! $active->subscription_plan_id) {
            return 'assign';
        }

        if ((int) $active->subscription_plan_id === (int) $targetPlan->id) {
            return 'renew';
        }

        return match ($this->comparePlans($active, $targetPlan)) {
            'higher' => 'upgrade',
            'lower' => 'downgrade',
            default => 'renew',
        };
    }

    protected function comparePlans(CompanySubscription $active, SubscriptionPlan $targetPlan): string
    {
        $currentLimit = (int) $active->staff_limit;
        $targetLimit = (int) $targetPlan->staff_limit;

        if ($targetLimit > $currentLimit) {
            return 'higher';
        }

        if ($targetLimit < $currentLimit) {
            return 'lower';
        }

        $currentPlan = $active->plan;

        if ($currentPlan) {
            if ((int) $targetPlan->sort_order > (int) $currentPlan->sort_order) {
                return 'higher';
            }

            if ((int) $targetPlan->sort_order < (int) $currentPlan->sort_order) {
                return 'lower';
            }
        }

        return 'equal';
    }

    protected function expireActiveSubscriptions(Company $company, ?User $performer, string $reason): void
    {
        CompanySubscription::forCompany($company->id)
            ->active()
            ->each(function (CompanySubscription $subscription) use ($performer, $reason) {
                $before = clone $subscription;
                $subscription->update([
                    'status' => 'expired',
                    'expired_at' => now(),
                    'updated_by' => $performer?->id,
                ]);
                $this->recordHistory($subscription, 'expired', $performer, null, $subscription, $before, $reason);
            });
    }

    /**
     * Serialize subscription mutations per company and enforce one active row.
     * Uses row-level lock on companies + post-mutation reconciliation.
     */
    protected function withCompanySubscriptionLock(Company $company, callable $callback, ?User $performer = null): mixed
    {
        return DB::transaction(function () use ($company, $callback, $performer) {
            $lockedCompany = Company::where('id', $company->id)->lockForUpdate()->firstOrFail();
            $result = $callback($lockedCompany);
            $this->enforceSingleActiveSubscription($lockedCompany, $performer);

            return $result;
        });
    }

    protected function enforceSingleActiveSubscription(Company $company, ?User $performer = null): void
    {
        $activeSubscriptions = CompanySubscription::forCompany($company->id)
            ->active()
            ->orderByDesc('id')
            ->get();

        if ($activeSubscriptions->count() <= 1) {
            return;
        }

        $activeSubscriptions->slice(1)->each(function (CompanySubscription $subscription) use ($performer) {
            $before = clone $subscription;
            $subscription->update([
                'status' => 'expired',
                'expired_at' => now(),
                'updated_by' => $performer?->id,
            ]);
            $this->recordHistory(
                $subscription,
                'expired',
                $performer,
                null,
                $subscription,
                $before,
                'Enforced single active subscription rule'
            );
        });
    }

    protected function syncCompanyFromSubscription(Company $company, CompanySubscription $subscription, string $status): void
    {
        $company->update([
            'status' => $status,
            'expiry_date' => $subscription->expiry_date,
            'selected_user_limit' => $subscription->staff_limit,
        ]);
    }

    protected function recordHistory(
        ?CompanySubscription $subscription,
        string $eventType,
        ?User $performer,
        ?SubscriptionPayment $payment = null,
        ?CompanySubscription $after = null,
        ?CompanySubscription $before = null,
        ?string $notes = null
    ): void {
        if (! $subscription && ! $payment) {
            return;
        }

        SubscriptionHistory::create([
            'company_id' => $subscription?->company_id ?? $payment?->company_id,
            'company_subscription_id' => $after?->id ?? $subscription?->id,
            'subscription_payment_id' => $payment?->id,
            'event_type' => $eventType,
            'subscription_type_before' => $before?->subscription_type,
            'subscription_type_after' => $after?->subscription_type ?? $subscription?->subscription_type,
            'subscription_plan_id_before' => $before?->subscription_plan_id,
            'subscription_plan_id_after' => $after?->subscription_plan_id ?? $subscription?->subscription_plan_id,
            'billing_cycle_id_before' => $before?->billing_cycle_id,
            'billing_cycle_id_after' => $after?->billing_cycle_id ?? $subscription?->billing_cycle_id,
            'status_before' => $before?->status,
            'status_after' => $after?->status ?? $subscription?->status,
            'start_date_before' => $before?->start_date,
            'start_date_after' => $after?->start_date ?? $subscription?->start_date,
            'expiry_date_before' => $before?->expiry_date,
            'expiry_date_after' => $after?->expiry_date ?? $subscription?->expiry_date,
            'staff_limit_before' => $before?->staff_limit,
            'staff_limit_after' => $after?->staff_limit ?? $subscription?->staff_limit,
            'hidden_modules_before' => $before?->hidden_modules,
            'hidden_modules_after' => $after?->hidden_modules ?? $subscription?->hidden_modules,
            'performed_by' => $performer?->id,
            'notes' => $notes,
            'event_at' => now(),
            'created_at' => now(),
        ]);
    }
}
