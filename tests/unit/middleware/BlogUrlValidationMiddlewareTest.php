<?php namespace Golem15\Apparatus\Tests\Unit\Middleware;

use Golem15\Apparatus\Middleware\BlogUrlValidationMiddleware;
use Golem15\Apparatus\Tests\PluginTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use ReflectionMethod;

/**
 * Tests for BlogUrlValidationMiddleware.
 *
 * We test the URL route parsing methods and config parsing methods via reflection
 * since they are protected. We also test handle() for the disabled middleware
 * and non-blog URL pass-through cases.
 *
 * validateCategoryRoute() and validatePostRoute() query Winter\Blog models directly
 * and are documented as needing integration test coverage; they are not unit-tested here.
 */
class BlogUrlValidationMiddlewareTest extends PluginTestCase
{
    private BlogUrlValidationMiddleware $middleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = new BlogUrlValidationMiddleware();
    }

    // -------------------------------------------------------------------------
    // parseBlogRoute — non-blog paths return null
    // -------------------------------------------------------------------------

    public function testParseBlogRouteReturnsNullForNonBlogPaths(): void
    {
        // Set configured routes to 'blog' only
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/about', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNull($result);
    }

    public function testParseBlogRouteReturnsNullForContactPath(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/contact', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNull($result);
    }

    public function testParseBlogRouteReturnsNullForSingleSegment(): void
    {
        // '/blog' alone (single segment) has count($segments) === 1 after trim,
        // which is neither 2 nor 3 — returns null
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/blog', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNull($result);
    }

    public function testParseBlogRouteReturnsNullForFourSegments(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/blog/cat/slug/extra', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // parseBlogRoute — category route
    // -------------------------------------------------------------------------

    public function testParseBlogRouteReturnsCategoryInfoForTwoSegments(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/blog/tech', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNotNull($result);
        $this->assertSame('category', $result['type']);
        $this->assertSame('blog', $result['base']);
        $this->assertSame('tech', $result['category']);
    }

    // -------------------------------------------------------------------------
    // parseBlogRoute — post route
    // -------------------------------------------------------------------------

    public function testParseBlogRouteReturnsPostInfoForThreeSegments(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $request = Request::create('/blog/tech/my-post', 'GET');
        $result = $this->callParseBlogRoute($request);

        $this->assertNotNull($result);
        $this->assertSame('post', $result['type']);
        $this->assertSame('blog', $result['base']);
        $this->assertSame('tech', $result['category']);
        $this->assertSame('my-post', $result['slug']);
    }

    // -------------------------------------------------------------------------
    // getConfiguredRoutes — config parsing
    // -------------------------------------------------------------------------

    public function testGetConfiguredRoutesReturnsArrayForStringConfig(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        $result = $this->callGetConfiguredRoutes();

        $this->assertSame(['blog'], $result);
    }

    public function testGetConfiguredRoutesParseCommaSeparatedString(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', 'news, blog');

        $result = $this->callGetConfiguredRoutes();

        $this->assertSame(['news', 'blog'], $result);
    }

    public function testGetConfiguredRoutesReturnsArrayAsIs(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', ['news', 'blog', 'articles']);

        $result = $this->callGetConfiguredRoutes();

        $this->assertSame(['news', 'blog', 'articles'], $result);
    }

    public function testGetConfiguredRoutesTrimsWhitespace(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.routes', ' news , blog , tech ');

        $result = $this->callGetConfiguredRoutes();

        $this->assertSame(['news', 'blog', 'tech'], $result);
    }

    // -------------------------------------------------------------------------
    // generateCorrectPostUrl
    // -------------------------------------------------------------------------

    public function testGenerateCorrectPostUrlReturnsExpectedFormat(): void
    {
        $result = $this->callGenerateCorrectPostUrl('blog', 'technology', 'my-article');

        $this->assertStringEndsWith('/blog/technology/my-article', $result);
    }

    public function testGenerateCorrectPostUrlWithNewsBlogBase(): void
    {
        $result = $this->callGenerateCorrectPostUrl('news', 'sports', 'championship');

        $this->assertStringEndsWith('/news/sports/championship', $result);
    }

    // -------------------------------------------------------------------------
    // handle — middleware disabled via config
    // -------------------------------------------------------------------------

    public function testHandlePassesThroughWhenMiddlewareDisabled(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.enabled', false);

        $request = Request::create('/blog/tech/my-post', 'GET');
        $passed = false;

        $next = function (Request $req) use (&$passed) {
            $passed = true;
            return response()->json(['ok' => true]);
        };

        $this->middleware->handle($request, $next);

        $this->assertTrue($passed);
    }

    // -------------------------------------------------------------------------
    // handle — non-blog URL passes through
    // -------------------------------------------------------------------------

    public function testHandlePassesThroughForNonBlogUrl(): void
    {
        Config::set('golem15.apparatus::blog.url_validation.enabled', true);
        Config::set('golem15.apparatus::blog.url_validation.routes', 'blog');

        // Use /about — not in configured blog routes
        $request = Request::create('/about', 'GET');
        $passed = false;

        $next = function (Request $req) use (&$passed) {
            $passed = true;
            return response()->json(['ok' => true]);
        };

        $this->middleware->handle($request, $next);

        $this->assertTrue($passed);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function callParseBlogRoute(Request $request): ?array
    {
        $method = new ReflectionMethod(BlogUrlValidationMiddleware::class, 'parseBlogRoute');
        $method->setAccessible(true);
        return $method->invoke($this->middleware, $request);
    }

    private function callGetConfiguredRoutes(): array
    {
        $method = new ReflectionMethod(BlogUrlValidationMiddleware::class, 'getConfiguredRoutes');
        $method->setAccessible(true);
        return $method->invoke($this->middleware);
    }

    private function callGenerateCorrectPostUrl(string $base, string $categorySlug, string $postSlug): string
    {
        $method = new ReflectionMethod(BlogUrlValidationMiddleware::class, 'generateCorrectPostUrl');
        $method->setAccessible(true);
        return $method->invoke($this->middleware, $base, $categorySlug, $postSlug);
    }
}
