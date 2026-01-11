<?php namespace Golem15\Apparatus\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Winter\Blog\Models\Post;

/**
 * Blog URL Validation Middleware
 *
 * Validates Winter.Blog URLs to fix SEO issues:
 * - Returns 404 for non-existing categories
 * - Returns 301 redirect for posts with incorrect category URLs
 */
class BlogUrlValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Check if middleware is enabled
        if (!Config::get('golem15.apparatus::blog.url_validation.enabled', true)) {
            return $next($request);
        }

        // 2. Check if Winter.Blog plugin exists
        if (!$this->isBlogPluginInstalled()) {
            return $next($request);
        }

        // 3. Parse URL to detect blog route pattern
        $routeInfo = $this->parseBlogRoute($request);

        if (!$routeInfo) {
            return $next($request); // Not a blog route
        }

        // 4. Validate based on route type
        if ($routeInfo['type'] === 'category') {
            return $this->validateCategoryRoute($request, $next, $routeInfo);
        }

        if ($routeInfo['type'] === 'post') {
            return $this->validatePostRoute($request, $next, $routeInfo);
        }

        return $next($request);
    }

    /**
     * Check if Winter.Blog plugin is installed
     *
     * @return bool
     */
    protected function isBlogPluginInstalled(): bool
    {
        return class_exists(\Winter\Blog\Models\Post::class)
            && class_exists(\Winter\Blog\Models\Category::class);
    }

    /**
     * Parse request URL to detect blog route pattern
     *
     * Returns: ['type' => 'category'|'post', 'base' => 'news', 'category' => 'slug', 'slug' => 'slug']
     * or null if not a blog route
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    protected function parseBlogRoute(Request $request): ?array
    {
        $path = trim($request->path(), '/');
        $segments = explode('/', $path);

        // Get configured blog routes
        $routes = $this->getConfiguredRoutes();

        if (empty($segments) || !in_array($segments[0], $routes)) {
            return null;
        }

        $base = $segments[0];

        // Pattern: /news/:category
        if (count($segments) === 2) {
            return [
                'type' => 'category',
                'base' => $base,
                'category' => $segments[1],
            ];
        }

        // Pattern: /news/:category/:slug
        if (count($segments) === 3) {
            return [
                'type' => 'post',
                'base' => $base,
                'category' => $segments[1],
                'slug' => $segments[2],
            ];
        }

        return null;
    }

    /**
     * Get configured blog routes as array
     *
     * @return array
     */
    protected function getConfiguredRoutes(): array
    {
        $routes = Config::get('golem15.apparatus::blog.url_validation.routes', 'blog');

        if (is_array($routes)) {
            return $routes;
        }

        // Parse comma-separated string
        return array_map('trim', explode(',', $routes));
    }

    /**
     * Validate category route
     * Returns 404 if category doesn't exist
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function validateCategoryRoute(Request $request, Closure $next, array $routeInfo)
    {
        $categorySlug = $routeInfo['category'];

        // Query category
        $category = \Winter\Blog\Models\Category::query()
            ->where('slug', $categorySlug)
            ->first();

        if (!$category) {
            Log::info('BlogUrlValidationMiddleware: Invalid category', [
                'url' => $request->url(),
                'category_slug' => $categorySlug,
            ]);

            return $this->notFoundResponse();
        }

        return $next($request);
    }

    /**
     * Validate post route
     * - Returns 404 if post doesn't exist
     * - Returns 301 redirect if category is incorrect
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function validatePostRoute(Request $request, Closure $next, array $routeInfo)
    {
        $categorySlug = $routeInfo['category'];
        $postSlug = $routeInfo['slug'];

        // Query post with its categories
        $post = \Winter\Blog\Models\Post::query()
            ->with('categories')
            ->where('slug', $postSlug)
            ->first();

        // Post doesn't exist -> 404
        if (!$post) {
            Log::info('BlogUrlValidationMiddleware: Post not found', [
                'url' => $request->url(),
                'post_slug' => $postSlug,
            ]);

            $this->notFoundResponse();
        }

        // Get post's primary category
        $primaryCategory = $post->categories->first();

        // Post has no categories -> Allow through (edge case)
        if (!$primaryCategory) {
            Log::warning('BlogUrlValidationMiddleware: Post has no categories', [
                'post_id' => $post->id,
                'post_slug' => $postSlug,
            ]);

            return $next($request);
        }

        // Category mismatch -> 301 redirect to correct URL
        if ($primaryCategory->slug !== $categorySlug) {
            $correctUrl = $this->generateCorrectPostUrl(
                $routeInfo['base'],
                $primaryCategory->slug,
                $postSlug
            );

            Log::info('BlogUrlValidationMiddleware: Redirecting to correct category', [
                'from_url' => $request->url(),
                'to_url' => $correctUrl,
                'wrong_category' => $categorySlug,
                'correct_category' => $primaryCategory->slug,
            ]);
            // actually advised to return 404 instead of redirect
            $this->notFoundResponse();
            // return $this->redirectResponse($correctUrl);
        }

        return $next($request);
    }

    /**
     * Generate correct post URL
     *
     * @param  string  $base
     * @param  string  $categorySlug
     * @param  string  $postSlug
     * @return string
     */
    protected function generateCorrectPostUrl(string $base, string $categorySlug, string $postSlug): string
    {
        return url("/{$base}/{$categorySlug}/{$postSlug}");
    }

    /**
     * Return 404 Not Found response
     */
    protected function notFoundResponse()
    {
        $statusCode = Config::get('golem15.apparatus::blog.url_validation.status_codes.not_found', 404);
        abort($statusCode);
    }

    /**
     * Return 301 Permanent Redirect response
     *
     * @param  string  $url
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectResponse(string $url)
    {
        $statusCode = Config::get('golem15.apparatus::blog.url_validation.status_codes.permanent_redirect', 301);

        return Redirect::to($url, $statusCode);
    }
}
