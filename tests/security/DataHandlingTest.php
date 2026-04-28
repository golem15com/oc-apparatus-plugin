<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Security PoC tests for HIGH finding APP-004.
 * Each test method is named test_app_NNN_<short_slug> and references
 * the finding in .planning/audit/plugins/golem15/apparatus/FINDINGS.md.
 *
 * @group security
 *
 * Per Phase 7 D-20: PoC tests use HTTP-only + unit fidelity.
 * These tests MUST FAIL on current code (red-bar regression locks).
 * The remediation milestone's fixes will turn them green.
 */
class DataHandlingTest extends PluginTestCase
{
    /**
     * APP-004: ForceJsonResponse middleware leaks exception details in production.
     *
     * The middleware catches all Throwable exceptions and returns $e->getMessage()
     * unconditionally (line 25: $data = ['error' => $e->getMessage()]). Even with
     * app.debug=false, raw exception messages are returned. Exception messages from
     * database drivers, framework internals, or third-party libraries contain
     * internal details: table names, column names, SQL fragments, class paths.
     *
     * EXPECTATION (post-fix): Non-HttpException exceptions return a generic
     * "Internal server error" message when app.debug is false.
     * TODAY (pre-fix): $e->getMessage() is always returned regardless of debug mode.
     * This assertion FAILS because the generic error message pattern is absent.
     *
     * @test
     * @group security
     * @see .planning/audit/plugins/golem15/apparatus/FINDINGS.md #APP-004
     * @see .planning/audit/DASHBOARD.md #APP-004
     */
    public function test_app_004_forcejson_exception_message_leakage(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/middleware/ForceJsonResponse.php'
        );

        // The secure pattern checks whether the exception is an HttpException or
        // a known safe type before returning getMessage(). For generic exceptions
        // in production, it should return a fixed string.
        // We verify the middleware differentiates exception types when app.debug=false.
        $hasExceptionTypeCheck = (
            str_contains($source, 'HttpException')
            || str_contains($source, 'instanceof')
            || str_contains($source, 'ValidationException')
        ) && (
            str_contains($source, 'Internal server error')
            || str_contains($source, 'generic')
            || str_contains($source, 'An error occurred')
        );

        $this->assertTrue(
            $hasExceptionTypeCheck,
            'APP-004: ForceJsonResponse middleware returns $e->getMessage() for ALL '
            . 'exceptions unconditionally, including database errors, framework errors, '
            . 'and third-party library errors. Exception messages leak internal details '
            . '(table names, column names, SQL fragments, class paths) to API clients. '
            . 'Post-fix: return generic "Internal server error" for non-HttpException '
            . 'exceptions when app.debug is false; only return getMessage() for known '
            . 'safe exception types (ValidationException, AuthenticationException).'
        );
    }
}
