<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\RouteResolver;
use Golem15\Apparatus\Tests\PluginTestCase;
use ReflectionMethod;

/**
 * Tests for the RouteResolver class.
 *
 * RouteResolver::__construct() calls Theme::getActiveTheme() and Page::listInTheme()
 * which require CMS infrastructure. We test only the two pure utility methods
 * (urlMatchesPattern and stripUrlParameters) via reflection, bypassing the
 * constructor entirely.
 */
class RouteResolverTest extends PluginTestCase
{
    private RouteResolver $resolver;

    public function setUp(): void
    {
        parent::setUp();

        // Bypass the constructor using ReflectionClass to avoid CMS dependency
        $ref = new \ReflectionClass(RouteResolver::class);
        $this->resolver = $ref->newInstanceWithoutConstructor();
    }

    // -------------------------------------------------------------------------
    // urlMatchesPattern — exact match
    // -------------------------------------------------------------------------

    public function testExactMatchReturnsTrue(): void
    {
        $result = $this->callUrlMatchesPattern('/blog', '/blog');

        $this->assertTrue($result);
    }

    public function testRootExactMatch(): void
    {
        $result = $this->callUrlMatchesPattern('/', '/');

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // urlMatchesPattern — no dynamic parts, different URL returns false
    // -------------------------------------------------------------------------

    public function testStaticPatternWithDifferentUrlReturnsFalse(): void
    {
        $result = $this->callUrlMatchesPattern('/contact', '/blog');

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // urlMatchesPattern — dynamic pattern matching
    // -------------------------------------------------------------------------

    public function testPatternWithParamMatchesUrlWithSameSegmentCount(): void
    {
        // /blog/:category matches /blog/technology
        $result = $this->callUrlMatchesPattern('/blog/technology', '/blog/:category');

        $this->assertTrue($result);
    }

    public function testPatternWithTwoParamsMatchesUrl(): void
    {
        // /blog/:category/:slug matches /blog/tech/my-post
        $result = $this->callUrlMatchesPattern('/blog/tech/my-post', '/blog/:category/:slug');

        $this->assertTrue($result);
    }

    public function testDifferentSegmentCountReturnsFalse(): void
    {
        // /blog has 2 parts, /blog/:category/:slug expects 4
        $result = $this->callUrlMatchesPattern('/blog', '/blog/:category/:slug');

        $this->assertFalse($result);
    }

    public function testStaticSegmentMismatchReturnsFalse(): void
    {
        // /news/tech doesn't match /blog/:category
        $result = $this->callUrlMatchesPattern('/news/tech', '/blog/:category');

        $this->assertFalse($result);
    }

    public function testStaticSegmentsMustMatchLiterally(): void
    {
        // /blog/tech/post matches /blog/:cat/:post
        $result = $this->callUrlMatchesPattern('/blog/tech/post', '/blog/:cat/:post');
        $this->assertTrue($result);

        // /admin/tech/post does NOT match /blog/:cat/:post (static 'blog' vs 'admin')
        $result = $this->callUrlMatchesPattern('/admin/tech/post', '/blog/:cat/:post');
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // stripUrlParameters
    // -------------------------------------------------------------------------

    public function testUrlWithoutParamsReturnedUnchanged(): void
    {
        $result = $this->resolver->stripUrlParameters('/blog');

        $this->assertSame('/blog', $result);
    }

    public function testRootUrlReturnedUnchanged(): void
    {
        $result = $this->resolver->stripUrlParameters('/');

        $this->assertSame('/', $result);
    }

    public function testUrlWithParamReturnsEverythingBeforeFirstParam(): void
    {
        $result = $this->resolver->stripUrlParameters('/blog/:category');

        $this->assertSame('/blog', $result);
    }

    public function testUrlWithTwoParamsReturnsBasePathOnly(): void
    {
        $result = $this->resolver->stripUrlParameters('/blog/:category/:slug');

        $this->assertSame('/blog', $result);
    }

    public function testUrlWithDeepBaseAndParam(): void
    {
        $result = $this->resolver->stripUrlParameters('/news/articles/:slug');

        $this->assertSame('/news/articles', $result);
    }

    // -------------------------------------------------------------------------
    // Helper: invoke protected urlMatchesPattern() via reflection
    // -------------------------------------------------------------------------

    private function callUrlMatchesPattern(string $url, string $pattern): bool
    {
        $method = new ReflectionMethod(RouteResolver::class, 'urlMatchesPattern');
        $method->setAccessible(true);
        return $method->invoke($this->resolver, $url, $pattern);
    }
}
