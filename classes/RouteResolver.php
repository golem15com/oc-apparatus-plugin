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
            return preg_replace('/\\:('.$parameterValue.')\\??/', $value, $url, -1);
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
