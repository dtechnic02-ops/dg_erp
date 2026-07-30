<?php

namespace Tests\Feature\Company;

use App\Services\SubscriptionService;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class CompanyAdminFixtureTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_company_admin_fixture_is_valid_for_company_route_middleware(): void
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $plan = $this->createActiveSubscriptionPlan();
        $subscription = $this->createOperationalCompanySubscription($company, $plan);
        $this->authenticateCompanyAdmin($user);

        $this->assertTrue(auth()->check());
        $this->assertSame($user->id, auth()->id());
        $this->assertContains($user->role_id, [2, 3]);
        $this->assertNotNull($user->company_id);
        $this->assertTrue($user->company()->exists());
        $this->assertSame('active', $company->status);
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'is_active' => 1]);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $subscription->id, 'company_id' => $company->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);
        $this->assertTrue($subscription->start_date->isPast());
        $this->assertTrue($subscription->expiry_date->isFuture());
        $this->assertTrue(app(SubscriptionService::class)->isSubscriptionOperational($company->fresh()));
    }
}
