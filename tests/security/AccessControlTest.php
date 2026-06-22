<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security PoC tests for HIGH findings APP-002, APP-003.
 * Each test method is named test_app_NNN_<short_slug> and references
 * the finding in .planning/audit/plugins/golem15/apparatus/FINDINGS.md.
 *
 * Per Phase 7 D-20: PoC tests use HTTP-only + unit fidelity.
 * These tests MUST FAIL on current code (red-bar regression locks).
 * The remediation milestone's fixes will turn them green.
 */
#[Group('security')]
class AccessControlTest extends PluginTestCase
{
    /**
     * APP-002: Protected file route allows path traversal via slug parameter.
     *
     * The route GET /storage/app/uploads/protected/{slug} uses a wildcard regex
     * (.*)?  for the slug parameter and constructs a filesystem path via string
     * concatenation: $path = storage_path().'/app/uploads/protected/'.$slug.
     * No sanitization of ../ sequences is applied to $slug.
     *
     * EXPECTATION (post-fix): The route sanitizes $slug to reject ../ sequences,
     * uses basename(), or resolves via realpath() comparison.
     * TODAY (pre-fix): $slug is used unsanitized in path construction.
     * This assertion FAILS because the sanitization pattern is absent.
     *
     * @see .planning/audit/plugins/golem15/apparatus/FINDINGS.md #APP-002
     * @see .planning/audit/DASHBOARD.md #APP-002
     */
    #[Test]
    #[Group('security')]
    public function test_app_002_protected_file_path_traversal(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/routes.php'
        );

        // EXPECTATION (post-fix): The route handler sanitizes the slug parameter
        // to prevent path traversal. It should use basename(), realpath() comparison,
        // or reject ../ sequences.
        // TODAY (pre-fix): No such sanitization exists in the route handler.
        $this->assertTrue(
            str_contains($source, 'basename(')
            || str_contains($source, 'realpath(')
            || str_contains($source, "str_replace('..',")
            || str_contains($source, 'Str::contains')
            || str_contains($source, "strpos(\$slug, '..')")
            || str_contains($source, 'sanitize'),
            'APP-002: Protected file route constructs filesystem path from unsanitized '
            . '$slug parameter. An authenticated backend user can traverse to arbitrary '
            . 'files via ../  sequences (e.g., /storage/app/uploads/protected/../../.env). '
            . 'Post-fix: sanitize $slug with basename() or validate resolved path is '
            . 'within the expected directory using realpath() comparison.'
        );
    }

    /**
     * APP-003: Jobs backend controller has no $requiredPermissions.
     *
     * The Jobs controller does not define $requiredPermissions, allowing any
     * authenticated backend user to access it via direct URL navigation.
     * This includes AJAX handlers for job cancellation, force-cancellation,
     * and deletion.
     *
     * EXPECTATION (post-fix): $requiredPermissions = ['golem15.apparatus.access_jobs']
     * TODAY (pre-fix): $requiredPermissions is not set.
     * This assertion FAILS because the property is absent.
     *
     * @see .planning/audit/plugins/golem15/apparatus/FINDINGS.md #APP-003
     * @see .planning/audit/DASHBOARD.md #APP-003
     */
    #[Test]
    #[Group('security')]
    public function test_app_003_missing_required_permissions_jobs(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/controllers/Jobs.php'
        );

        $this->assertTrue(
            str_contains($source, '$requiredPermissions')
            && !str_contains($source, '$requiredPermissions = []'),
            'APP-003: Jobs controller does not declare $requiredPermissions. '
            . 'Any authenticated backend user can view all background jobs, cancel '
            . 'running jobs (potentially disrupting payment processing or data imports), '
            . 'force-cancel jobs, and delete job records. '
            . 'Post-fix: add $requiredPermissions = [\'golem15.apparatus.access_jobs\'].'
        );
    }
}
