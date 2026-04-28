<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Security PoC tests for CRITICAL findings APP-001.
 * Each test method is named test_app_NNN_<short_slug> and references the finding ID
 * in .planning/audit/plugins/apparatus/FINDINGS.md.
 *
 * @group security
 *
 * Per Phase 3 D-20: these tests MUST FAIL on current code (red-bar regression locks).
 * Phase 8 remediation turns them green.
 */
class InjectionTest extends PluginTestCase
{
    /**
     * APP-001: ListToggle handler must validate model class against controller's list config.
     *
     * The handler in Plugin.php previously instantiated any class from post('model')
     * without validation (line 281: $model = new $modelClass). The fix validates that:
     * 1. The controller implements ListController behavior
     * 2. The model class matches the controller's listGetConfig()->modelClass
     * 3. The field is a listtoggle column in the controller's column config
     * 4. The controller's $requiredPermissions are enforced
     *
     * EXPECTATION (post-fix): Plugin.php validates model class against allowlist
     * (listGetConfig) and rejects arbitrary classes with InvalidArgumentException.
     *
     * @test
     * @group security
     * @see .planning/audit/plugins/apparatus/FINDINGS.md APP-001
     */
    public function test_app_001_listtoggle_arbitrary_class_instantiation(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/Plugin.php'
        );

        // Verify the handler validates model class against controller's list config
        // instead of blindly instantiating from user input
        $hasAllowlistValidation = str_contains($source, 'listGetConfig')
            && str_contains($source, 'modelClass')
            && (
                str_contains($source, 'InvalidArgumentException')
                || str_contains($source, 'not permitted')
            );

        $this->assertTrue(
            $hasAllowlistValidation,
            'APP-001: ListToggle handler instantiates arbitrary class from post(\'model\') '
            . 'without validation. An authenticated backend user can instantiate any PHP class '
            . 'and toggle any boolean field on any model record. '
            . 'Post-fix: validate model class against controller\'s listGetConfig()->modelClass '
            . 'allowlist and throw InvalidArgumentException for non-permitted classes.'
        );

        // Verify the old vulnerable pattern (direct instantiation from user input) is gone
        $hasUnsafeInstantiation = str_contains($source, '$model = new $modelClass');

        $this->assertFalse(
            $hasUnsafeInstantiation,
            'APP-001: The old insecure pattern "$model = new $modelClass" is still present '
            . 'in Plugin.php. The handler should use the validated $allowedModelClass instead.'
        );

        // Verify permission enforcement is present
        $hasPermissionCheck = str_contains($source, 'requiredPermissions')
            && str_contains($source, 'hasAccess');

        $this->assertTrue(
            $hasPermissionCheck,
            'APP-001: ListToggle handler does not enforce controller $requiredPermissions. '
            . 'Post-fix: check BackendAuth user hasAccess() against controller permissions.'
        );

        // Verify field validation against listtoggle column type
        $hasFieldValidation = str_contains($source, 'listtoggle')
            && str_contains($source, 'listGetColumns');

        $this->assertTrue(
            $hasFieldValidation,
            'APP-001: ListToggle handler does not validate field is a listtoggle column. '
            . 'Post-fix: validate field against controller listGetColumns() column config.'
        );

        // Verify uses findOrFail instead of find on validated model
        $hasSecureFind = str_contains($source, 'findOrFail');

        $this->assertTrue(
            $hasSecureFind,
            'APP-001: ListToggle handler should use findOrFail() on the validated model class.'
        );
    }
}
