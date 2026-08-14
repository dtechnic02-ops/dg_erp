<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\JournalController;
use App\Models\Journal;
use Illuminate\Http\Request;
use Tests\TestCase;

class JournalListUiTest extends TestCase
{
    public function test_status_filter_resolver_matches_query_behavior(): void
    {
        $controller = new JournalController(app(\App\Services\JournalService::class));
        $resolve = new \ReflectionMethod($controller, 'resolveListStatusFilter');
        $resolve->setAccessible(true);

        $this->assertSame(Journal::STATUS_DRAFT, $resolve->invoke($controller, Request::create('/company/journal', 'GET')));
        $this->assertSame('', $resolve->invoke($controller, Request::create('/company/journal', 'GET', ['status' => ''])));
        $this->assertSame(Journal::STATUS_POSTED, $resolve->invoke($controller, Request::create('/company/journal', 'GET', ['status' => Journal::STATUS_POSTED])));
    }

    public function test_edit_visibility_rule_uses_draft_unlocked_not_posted_active(): void
    {
        $posted = new Journal(['status' => Journal::STATUS_POSTED, 'is_locked' => false]);
        $draft = new Journal(['status' => Journal::STATUS_DRAFT, 'is_locked' => false]);
        $lockedDraft = new Journal(['status' => Journal::STATUS_DRAFT, 'is_locked' => true]);
        $submitted = new Journal(['status' => Journal::STATUS_SUBMITTED, 'is_locked' => false]);

        $this->assertFalse($posted->isDraft());
        $this->assertTrue($draft->isDraft() && !$draft->is_locked);
        $this->assertFalse($lockedDraft->isDraft() && !$lockedDraft->is_locked);
        $this->assertFalse($submitted->isDraft());
        $this->assertTrue($posted->isActive());
    }
}
