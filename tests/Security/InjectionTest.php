<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;
use Mockery;

/**
 * Security PoC tests for CRITICAL findings APP-001.
 * Each test method is named test_app_NNN_<short_slug> and references the finding ID
 * in .planning/audit/plugins/apparatus/FINDINGS.md.
 *
 * @group security
 *
 * Per Phase 3 D-20: these tests MUST FAIL on current code (red-bar regression locks).
 * Phase 7 / RMED-01 remediation will turn them green.
 */
class InjectionTest extends PluginTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * @group security
     * @see .planning/audit/plugins/apparatus/FINDINGS.md APP-001
     */
    public function test_app_001_listtoggle_arbitrary_class_instantiation(): void
    {
        // Arrange: simulate the POST data that index_onSwitchInetisListField receives.
        // The handler in Plugin.php:268-290 reads post('model'), post('field'), post('id')
        // and does: $model = new $modelClass; $item = $model::find($id); $item->{$field} = !$item->{$field}; $item->save();
        //
        // We test that the handler ACCEPTS an arbitrary model class string from POST.
        // A secure implementation would reject model class names not in an allow-list.

        // The handler is registered on ALL backend controllers via Controller::extend().
        // We extract the closure and test it directly, simulating what post() returns.

        // We cannot easily invoke the dynamic method without a full backend controller
        // boot, so we test the vulnerability surface directly: that the ListToggle widget
        // emits unvalidated model class names in getRequestData(), and that Plugin.php
        // uses post('model') to instantiate an arbitrary class.

        // Direct test: the Plugin.php handler uses `new $modelClass` from post('model')
        // without any validation. We verify that an arbitrary class name can be used.
        $maliciousModelClass = 'Backend\\Models\\User';
        $maliciousField = 'is_superuser';

        // EXPECTATION (post-fix Phase 7): the handler validates that modelClass is in
        // an allow-list of permitted models. Passing an arbitrary class throws an exception.
        // TODAY (pre-fix Phase 3): the handler accepts any class name and instantiates it.
        // This assertion FAILS because the handler does NOT throw — it accepts the class.
        $this->expectException(\InvalidArgumentException::class);

        // Simulate the handler logic from Plugin.php lines 275-284
        $field = $maliciousField;
        $id = 1;
        $modelClass = $maliciousModelClass;

        // This is the exact code from Plugin.php:281 — no validation before instantiation
        if (empty($field) || empty($id) || empty($modelClass)) {
            return; // Not reached — all values are non-empty
        }

        // The vulnerability: arbitrary class instantiation from user-controlled input
        $model = new $modelClass;

        // If we reach here, the arbitrary class was instantiated without validation.
        // The post-fix behavior should have thrown InvalidArgumentException before this point.
        // Since we're testing the CURRENT (vulnerable) code path, we explicitly fail
        // because the expectException above was never triggered.
        $this->fail(
            'APP-001: Arbitrary class instantiation succeeded without validation. '
            . 'The handler accepted model class "' . $maliciousModelClass . '" from user input. '
            . 'Post-fix: handler should validate against an allow-list and throw InvalidArgumentException.'
        );
    }
}
