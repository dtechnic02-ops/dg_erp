<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffAddFormAutofillTest extends TestCase
{
    public function test_add_staff_form_preserves_fields_and_declares_autofill_protection(): void
    {
        $blade = file_get_contents(resource_path('views/company/users/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/<form[^>]+method="POST"[^>]+company\.users\.store[^>]+autocomplete="off"[^>]*>/',
            $blade,
        );
        $this->assertMatchesRegularExpression('/<input[^>]+name="name"[^>]+id="name"[^>]+autocomplete="off"[^>]*>/', $blade);
        $this->assertMatchesRegularExpression('/<input[^>]+name="email"[^>]+id="email"[^>]+autocomplete="off"[^>]*>/', $blade);
        $this->assertMatchesRegularExpression('/<input[^>]+name="password"[^>]+id="password"[^>]+autocomplete="new-password"[^>]*>/', $blade);
        $this->assertStringContainsString('@csrf', $blade);
        $this->assertStringContainsString('name="job_role"', $blade);
    }
}
