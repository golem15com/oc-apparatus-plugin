/**
 * InfiniteScrollManager - Reusable infinite scroll utility with deduplication
 *
 * Part of Golem15.Apparatus plugin
 * Compatible with Winter CMS / Laravel paginator
 *
 * @author Golem15
 * @version 1.0.0
 *
 * @example
 * const scroller = new InfiniteScrollManager({
 *     gridSelector: '#quest-cards-grid',
 *     sentinelSelector: '#scroll-sentinel',
 *     dataAttribute: 'data-template-id',
 *     onLoadMore: function(page) {
 *         return new Promise((resolve, reject) => {
 *             Snowboard.request(null, 'component::onLoadMore', {
 *                 data: { page: page },
 *                 preloader: 'minimal',
 *                 success: resolve,
 *                 error: reject
 *             });
 *         });
 *     },
 *     debug: true
 * });
 */
class InfiniteScrollManager {
    /**
     * @param {Object} config Configuration object
     * @param {string} config.gridSelector - CSS selector for the items grid/container
     * @param {string} config.sentinelSelector - CSS selector for the scroll sentinel element
     * @param {string} config.dataAttribute - Data attribute for unique item IDs (e.g., 'data-template-id')
     * @param {Function} config.onLoadMore - Function that returns a Promise, receives page number
     * @param {string} [config.rootMargin='50px'] - IntersectionObserver root margin
     * @param {number} [config.initialPage=1] - Starting page number
     * @param {Function} [config.onSuccess] - Optional success callback
     * @param {Function} [config.onError] - Optional error callback
     * @param {boolean} [config.debug=false] - Enable debug logging
     */
    constructor(config) {
        // Validate required config
        if (!config.gridSelector) throw new Error('InfiniteScrollManager: gridSelector is required');
        if (!config.sentinelSelector) throw new Error('InfiniteScrollManager: sentinelSelector is required');
        if (!config.dataAttribute) throw new Error('InfiniteScrollManager: dataAttribute is required');
        if (!config.onLoadMore) throw new Error('InfiniteScrollManager: onLoadMore is required');

        this.config = {
            gridSelector: config.gridSelector,
            sentinelSelector: config.sentinelSelector,
            dataAttribute: config.dataAttribute,
            onLoadMore: config.onLoadMore,
            rootMargin: config.rootMargin || '50px',
            initialPage: config.initialPage || 1,
            onSuccess: config.onSuccess || null,
            onError: config.onError || null,
            debug: config.debug || false
        };

        this.state = {
            currentPage: this.config.initialPage,
            lastPage: null,
            loading: false,
            hasMore: true
        };

        this.observer = null;
        this.init();
    }

    /**
     * Initialize the IntersectionObserver
     */
    init() {
        const sentinel = document.querySelector(this.config.sentinelSelector);
        if (!sentinel) {
            console.error('[InfiniteScroll] Sentinel not found:', this.config.sentinelSelector);
            return;
        }

        // Create IntersectionObserver to watch sentinel
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.state.hasMore && !this.state.loading) {
                    if (this.config.debug) {
                        console.log('[InfiniteScroll] Sentinel visible, loading page', this.state.currentPage + 1);
                    }
                    this.loadMore();
                }
            });
        }, {
            rootMargin: this.config.rootMargin,
            threshold: 0
        });

        this.observer.observe(sentinel);

        if (this.config.debug) {
            console.log('[InfiniteScroll] Initialized for', this.config.gridSelector);
            console.log('[InfiniteScroll] Config:', this.config);
        }
    }

    /**
     * Load the next page of items
     */
    async loadMore() {
        // Guard against concurrent loads
        if (this.state.loading || !this.state.hasMore) {
            if (this.config.debug) {
                console.log('[InfiniteScroll] Skipped - loading:', this.state.loading, 'hasMore:', this.state.hasMore);
            }
            return;
        }

        // Set loading flag immediately to prevent race conditions
        this.state.loading = true;
        const nextPage = this.state.currentPage + 1;

        try {
            // Call user-provided onLoadMore function
            const response = await this.config.onLoadMore(nextPage);

            // Handle response
            if (response.html && response.html.trim() !== '') {
                this.appendItems(response.html);

                // Update pagination state (compatible with Winter paginator)
                this.state.currentPage = response.currentPage || nextPage;
                this.state.lastPage = response.lastPage || null;
                this.state.hasMore = response.hasMore !== undefined
                    ? response.hasMore
                    : (this.state.currentPage < this.state.lastPage);

                if (this.config.debug) {
                    console.log('[InfiniteScroll] Loaded page', this.state.currentPage, '/', this.state.lastPage);
                    console.log('[InfiniteScroll] Has more:', this.state.hasMore);
                }

                // Call success callback
                if (this.config.onSuccess) {
                    this.config.onSuccess(response);
                }
            } else {
                this.state.hasMore = false;
                if (this.config.debug) {
                    console.log('[InfiniteScroll] No more items');
                }
            }
        } catch (error) {
            console.error('[InfiniteScroll] Load failed:', error);
            if (this.config.onError) {
                this.config.onError(error);
            }
        } finally {
            // Always clear loading flag
            this.state.loading = false;
        }
    }

    /**
     * Append new items to grid with deduplication
     * @param {string} html HTML string containing new items
     */
    appendItems(html) {
        const grid = document.querySelector(this.config.gridSelector);
        if (!grid) {
            console.error('[InfiniteScroll] Grid not found:', this.config.gridSelector);
            return;
        }

        // Get existing item IDs to prevent duplicates
        const existingIds = new Set();
        grid.querySelectorAll(`[${this.config.dataAttribute}]`).forEach(item => {
            const id = item.getAttribute(this.config.dataAttribute);
            if (id) existingIds.add(id);
        });

        if (this.config.debug) {
            console.log('[InfiniteScroll] Existing IDs:', existingIds.size);
        }

        // Parse new items from HTML
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newItems = temp.querySelectorAll(`[${this.config.dataAttribute}]`);

        // Append only NEW items (skip duplicates)
        let appendedCount = 0;
        let skippedCount = 0;

        newItems.forEach(item => {
            const itemId = item.getAttribute(this.config.dataAttribute);

            if (!itemId) {
                // Item missing ID - append anyway (shouldn't happen but safe fallback)
                if (this.config.debug) {
                    console.warn('[InfiniteScroll] Item missing', this.config.dataAttribute);
                }
                grid.appendChild(item);
                appendedCount++;
            } else if (!existingIds.has(itemId)) {
                // New item - append it
                grid.appendChild(item);
                existingIds.add(itemId);
                appendedCount++;
            } else {
                // Duplicate - skip it
                skippedCount++;
                if (this.config.debug) {
                    console.log('[InfiniteScroll] Skipped duplicate:', itemId);
                }
            }
        });

        if (this.config.debug) {
            console.log(`[InfiniteScroll] Appended ${appendedCount} items, skipped ${skippedCount} duplicates`);
        }
    }

    /**
     * Reset pagination state (useful for filters)
     * @param {number} initialPage Starting page number (default: 1)
     */
    reset(initialPage = 1) {
        this.state.currentPage = initialPage;
        this.state.lastPage = null;
        this.state.loading = false;
        this.state.hasMore = true;

        if (this.config.debug) {
            console.log('[InfiniteScroll] State reset to page', initialPage);
        }
    }

    /**
     * Update pagination metadata (useful when filters change)
     * @param {Object} data Pagination data
     * @param {number} data.currentPage Current page number
     * @param {number} data.lastPage Last page number
     * @param {boolean} data.hasMore Whether more items exist
     */
    updatePagination(data) {
        if (data.currentPage !== undefined) this.state.currentPage = data.currentPage;
        if (data.lastPage !== undefined) this.state.lastPage = data.lastPage;
        if (data.hasMore !== undefined) {
            this.state.hasMore = data.hasMore;
        } else if (this.state.lastPage) {
            this.state.hasMore = this.state.currentPage < this.state.lastPage;
        }

        if (this.config.debug) {
            console.log('[InfiniteScroll] Pagination updated:', this.state);
        }
    }

    /**
     * Get current state
     * @returns {Object} Current state
     */
    getState() {
        return { ...this.state };
    }

    /**
     * Destroy observer and cleanup
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
            if (this.config.debug) {
                console.log('[InfiniteScroll] Observer disconnected');
            }
        }
    }
}

// Export to global scope for use in Winter CMS components
window.InfiniteScrollManager = InfiniteScrollManager;
