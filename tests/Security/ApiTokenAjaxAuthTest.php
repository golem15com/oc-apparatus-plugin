<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Security PoC tests for XPLG-002 / UTIL-07: API token AJAX handler auth guard.
 *
 * Uses file_get_contents + str_contains source-pattern analysis (consistent
 * with Phase 7 PoC convention in journal/tests/Security/AccessControlTest.php).
 *
 * @group security
 */
class ApiTokenAjaxAuthTest extends PluginTestCase
{
    protected $refreshPlugins = ['Golem15.Apparatus'];

    /**
     * UTIL-07 / XPLG-002: onCreateApiToken and onRevokeApiToken AJAX handlers
     * must reject unauthenticated requests with AccessDeniedHttpException.
     *
     * The handlers call BackendAuth::getUser() but do NOT guard against null.
     * Post-fix: each handler has `if (!$user) throw AccessDeniedHttpException`.
     * Today (pre-fix): no guard — assertion FAILS.
     *
     * @test
     * @group security
     */
    public function test_xplg_002_token_ajax_unauthed(): void
    {
        $pluginPath = dirname(__DIR__, 2) . '/Plugin.php';

        if (!file_exists($pluginPath)) {
            $this->fail(
                'XPLG-002: Could not locate apparatus Plugin.php. '
                . 'Manual verification needed.'
            );
            return;
        }

        $source = file_get_contents($pluginPath);

        // Check onCreateApiToken handler has auth guard
        $this->assertCreateTokenHasGuard($source);

        // Check onRevokeApiToken handler has auth guard
        $this->assertRevokeTokenHasGuard($source);
    }

    /**
     * Assert onCreateApiToken handler contains the auth guard pattern.
     */
    protected function assertCreateTokenHasGuard(string $source): void
    {
        // Find the onCreateApiToken handler closure body
        $marker = "'onCreateApiToken'";
        $pos = strpos($source, $marker);

        $this->assertNotFalse(
            $pos,
            'XPLG-002: Could not locate onCreateApiToken handler in Plugin.php.'
        );

        // Extract ~600 chars after the marker to capture the handler body
        $handlerBody = substr($source, $pos, 600);

        $hasGuard = (
            str_contains($handlerBody, 'if (!$user)')
            || str_contains($handlerBody, 'if (! $user)')
            || str_contains($handlerBody, 'if ($user === null)')
            || str_contains($handlerBody, 'if (is_null($user))')
        );

        $hasException = str_contains($handlerBody, 'AccessDeniedHttpException');

        $this->assertTrue(
            $hasGuard && $hasException,
            'XPLG-002: onCreateApiToken handler calls BackendAuth::getUser() but does '
            . 'not throw AccessDeniedHttpException when user is null. An unauthenticated '
            . 'caller can reach the handler body. Post-fix: add `if (!$user) throw new '
            . 'AccessDeniedHttpException(...)` immediately after getUser().'
        );
    }

    /**
     * Assert onRevokeApiToken handler contains the auth guard pattern.
     */
    protected function assertRevokeTokenHasGuard(string $source): void
    {
        $marker = "'onRevokeApiToken'";
        $pos = strpos($source, $marker);

        $this->assertNotFalse(
            $pos,
            'XPLG-002: Could not locate onRevokeApiToken handler in Plugin.php.'
        );

        $handlerBody = substr($source, $pos, 600);

        $hasGuard = (
            str_contains($handlerBody, 'if (!$user)')
            || str_contains($handlerBody, 'if (! $user)')
            || str_contains($handlerBody, 'if ($user === null)')
            || str_contains($handlerBody, 'if (is_null($user))')
        );

        $hasException = str_contains($handlerBody, 'AccessDeniedHttpException');

        $this->assertTrue(
            $hasGuard && $hasException,
            'XPLG-002: onRevokeApiToken handler calls BackendAuth::getUser() but does '
            . 'not throw AccessDeniedHttpException when user is null. An unauthenticated '
            . 'caller can reach the handler body, including firstOrFail() which can leak '
            . 'token existence. Post-fix: add `if (!$user) throw new '
            . 'AccessDeniedHttpException(...)` immediately after getUser().'
        );
    }
}
