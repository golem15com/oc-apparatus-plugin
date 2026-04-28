<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Security tests for the |raw_safe Twig filter (D-11, D-12, D-13).
 *
 * Tests validate that the HTMLPurifier-backed HtmlSanitizer strips dangerous
 * HTML (script, javascript:, on* handlers, non-allowlisted iframe hosts)
 * while preserving safe markup (p, strong, em, a[href], youtube/vimeo iframes).
 *
 * @group security
 */
class RawSafeFilterTest extends PluginTestCase
{
    protected $refreshPlugins = ['Golem15.Apparatus'];

    /**
     * Helper: invoke the raw_safe filter directly via Plugin method.
     */
    protected function applyRawSafe(?string $html): string
    {
        return (new \Golem15\Apparatus\Plugin(app()))->rawSafeFilter($html);
    }

    /**
     * D-12: <script> tags must be stripped entirely.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_strips_script_tag(): void
    {
        $result = $this->applyRawSafe('<p>x</p><script>alert(1)</script>');

        $this->assertStringContainsString('<p>x</p>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * D-12: javascript: URL scheme must be stripped from href attributes.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_strips_javascript_href(): void
    {
        $result = $this->applyRawSafe('<a href="javascript:alert(1)">x</a>');

        $this->assertStringContainsString('>x</a>', $result);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    /**
     * D-12: onerror (and all on* event handlers) must be stripped.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_strips_onerror_attribute(): void
    {
        $result = $this->applyRawSafe('<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('onerror', $result);
    }

    /**
     * D-12: Allowlisted tags (p, strong, em) pass through unchanged.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_allows_paragraph_strong_em(): void
    {
        $result = $this->applyRawSafe('<p>hello <strong>world</strong></p>');

        $this->assertEquals('<p>hello <strong>world</strong></p>', trim($result));
    }

    /**
     * D-12: https links in a[href] are preserved.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_allows_https_link(): void
    {
        $result = $this->applyRawSafe('<a href="https://example.com">link</a>');

        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    /**
     * D-12: YouTube iframe src is allowed (host-restricted allowlist).
     *
     * @test
     * @group security
     */
    public function test_raw_safe_allows_youtube_iframe(): void
    {
        $result = $this->applyRawSafe('<iframe src="https://www.youtube.com/embed/abc123"></iframe>');

        $this->assertStringContainsString('youtube.com/embed/abc123', $result);
    }

    /**
     * D-12: Non-allowlisted iframe hosts must be stripped.
     *
     * @test
     * @group security
     */
    public function test_raw_safe_strips_evil_iframe_host(): void
    {
        $result = $this->applyRawSafe('<iframe src="https://evil.com/x"></iframe>');

        $this->assertStringNotContainsString('evil.com', $result);
    }

    /**
     * Null input must return empty string (no crash).
     *
     * @test
     * @group security
     */
    public function test_raw_safe_handles_null_input(): void
    {
        $result = $this->applyRawSafe(null);

        $this->assertSame('', $result);
    }
}
