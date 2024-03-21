<?php namespace Golem15\Apparatus\Classes;

use Illuminate\Contracts\Logging\Log;
use Illuminate\Contracts\Config\Repository;
use Cms\Classes\Theme;
use Cms\Classes\Page;
use October\Rain\Exception\ApplicationException;
use Psr\Log\LoggerInterface;

/**
 * Class RouteResolver
 *
 * @package Golem15\Apparatus\Classes
 */
class RouteResolver
{
    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @var Page[]
     */
    protected $pages;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $componentPageCache = [];

    /**
     * RouteResolver constructor.
     *
     * @param Repository $config
     * @param Log        $log
     */
    public function __construct(Repository $config, LoggerInterface $log)
    {
        $this->theme = Theme::getActiveTheme();
        $this->pages = Page::listInTheme($this->theme, true);
        $this->log = $log;
        $this->config = $config;
    }

    /**
     * @param string $component
     *
     * @return Page|null
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function getPageWithComponent(string $component): ?Page
    {
        if (isset($this->componentPageCache[$component])) {
            return $this->componentPageCache[$component];
        }

        /**
         * @var \Cms\Classes\Page $page
         */
        foreach ($this->pages as $page) {
            if ($page->hasComponent($component)) {
                $this->componentPageCache[$component] = $page;

                return $page;
            }
        }

        $this->componentNotFound($component);

        return null;
    }

    /**
     * @param string $component
     *
     * @return string|null
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function resolveRouteTo(string $component): ?string
    {
        if ($page = $this->getPageWithComponent($component)) {
            return $page->settings['url'];
        }

        return null;
    }

    /**
     * @param string $component
     *
     * @return string|null
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function resolvePageForUrl(string $url): ?Page
    {
        $url = '/' . ltrim($url, '/');
        $page = Page::where('url', $url)->first();
        if ($page) {
            return $page;
        }

        // Manually fetch all pages and iterate to find a match
        // Note: Consider optimizing this if you have a large number of pages
        $pages = Page::all();
        foreach ($pages as $page) {
            if ($this->urlMatchesPattern($url, $page->url)) {
                return $page;
            }
        }

        // Return null if no page matches the dynamic pattern
        return null;
    }

    /**
     * Check if the given URL matches a defined URL pattern.
     * Adjust the logic here based on your actual URL patterns and requirements.
     * Example patterns will be:
     * /blog
     * /
     * /blog/:category/:post
     * /user/:slug
     * /blog/:category
     *
     * Example $url will be
     * /blog
     * /
     * /blog/technology/why-laravel-is-awesome
     * /user/john-doe
     * /blog/technology
     */
    protected function urlMatchesPattern(string $url, string $pattern): bool
    {
        // If the URL and pattern are an exact match, return true
        if ($url === $pattern) {
            return true;
        }

        // If the pattern has no dynamic parts, return false
        if (strpos($pattern, '/:') === false) {
            return false;
        }

        // If the pattern has dynamic parts, extract them and compare
        $patternParts = explode('/', $pattern);
        $urlParts = explode('/', $url);

        // If the number of parts don't match, return false
        if (count($patternParts) !== count($urlParts)) {
            return false;
        }

        // Iterate through the parts and compare
        foreach ($patternParts as $index => $part) {
            if (strpos($part, ':') === false) {
                if ($part !== $urlParts[$index]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function stripUrlParameters(string $url): string
    {
        if (strpos($url, '/:') !== false) {
            $parts = explode('/:', $url);

            return $parts[0];
        }

        return $url;
    }

    /**
     * @param string $component
     *
     * @return string
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function resolveRouteWithoutParamsTo(string $component): string
    {
        $page = $this->getPageWithComponent($component);

        /*
         * In production, on broken component links, return /error for graceful error handling
         */
        if (!$page) {
            return '/error';
        }

        $url = $this->resolveRouteTo($component);

        return $this->stripUrlParameters($url);
    }

    /**
     * @param string $component
     * @param string $parameter
     * @param string $value
     *
     * @return mixed|null|string
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function resolveParameterizedRouteTo(string $component, string $parameter, string $value): ?string
    {
        $page = $this->getPageWithComponent($component);

        /*
         * In production, on broken component links, return /error for graceful error handling
         */
        if (!$page) {
            return '/error';
        }

        $url = $this->resolveRouteTo($component);

        $properties = $page->getComponentProperties($component);

        /*
         * In production, on broken component links, return /error for graceful error handling
         */
        if (!array_key_exists($parameter, $properties)) {
            $this->parameterNotFound($parameter, $component);

            return '/error';
        }

        $parameterValue = $properties[$parameter];

        /*
         * Strip external parameter tags, ie {{ :code }} -> code
         */
        if (strpos($parameterValue, '{') !== false) {
            $parameterValue = trim(str_replace('{', '', str_replace('}', '', str_replace(':', '', $parameterValue))));

            // also: :code -> code
        } elseif (strpos($parameterValue, ':') !== false) {
            $parameterValue = trim(str_replace(':', '', $parameterValue));
        }

        if (strpos($url, ':') !== false) {
            return preg_replace('/\\:(' . $parameterValue . ')\\??/', $value, $url, -1);
        }

        return null;
    }

    /**
     * @param string $component
     *
     * @throws \ApplicationException
     */
    protected function componentNotFound(string $component): void
    {
        if ($this->config->get('app.debug')) {
            throw new \ApplicationException(
                sprintf(trans('golem15.apparatus::lang.errors.pageWithComponentNotFound'), $component)
            );
        }

        $this->log->error(sprintf(trans('golem15.apparatus::lang.errors.pageWithComponentNotFound'), $component));
    }

    /**
     * @param string $parameter
     * @param string $component
     *
     * @throws ApplicationException
     */
    protected function parameterNotFound(string $parameter, string $component): void
    {
        if ($this->config->get('app.debug')) {
            throw new ApplicationException(
                sprintf(trans('golem15.apparatus::lang.errors.parameterNotFound'), $parameter, $component)
            );
        }

        $this->log->error(
            sprintf(trans('golem15.apparatus::lang.errors.parameterNotFound'), $parameter, $component)
        );
    }
}
