<?php namespace Golem15\Apparatus\Components;

use Cms\Classes\ComponentBase;

/**
 * InfiniteScroll Component
 *
 * Provides infinite scroll functionality with automatic deduplication,
 * observer management, and race condition prevention.
 *
 * This component injects the InfiniteScrollManager JavaScript utility
 * when included on a page. Pages that need infinite scroll functionality
 * should include this component.
 *
 * Usage in page:
 * [infiniteScroll]
 * ==
 * {% component 'infiniteScroll' %}
 *
 * Then in JavaScript:
 * const scroller = new InfiniteScrollManager({
 *     gridSelector: '#my-grid',
 *     sentinelSelector: '#scroll-sentinel',
 *     dataAttribute: 'data-item-id',
 *     onLoadMore: function(page) {
 *         return new Promise((resolve, reject) => {
 *             Snowboard.request(null, 'component::onLoadMore', {
 *                 data: { page: page },
 *                 preloader: 'minimal',
 *                 success: resolve,
 *                 error: reject
 *             });
 *         });
 *     }
 * });
 *
 * @package Golem15\Apparatus
 * @author Golem15
 */
class InfiniteScroll extends ComponentBase
{
    /**
     * Component Details
     */
    public function componentDetails()
    {
        return [
            'name' => 'Infinite Scroll',
            'description' => 'Provides infinite scroll utility for paginated content with automatic deduplication'
        ];
    }

    /**
     * Inject the infinite scroll JavaScript utility
     */
    public function onRun()
    {
        $this->addJs('assets/js/infinite-scroll.js');
    }
}
