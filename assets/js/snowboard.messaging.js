/**
 * Apparatus Messaging - Snowboard Plugin
 *
 * Provides notification functionality using Noty library
 * Replaces jQuery-based framework.messaging.js with Snowboard API
 */

(function () {
    if (typeof Snowboard === 'undefined') {
        console.error('Apparatus Messaging: Snowboard not available');
        return;
    }

    // Wait for Snowboard / DOM to be ready (same pattern as confirm-handler.js)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('Apparatus Messaging: Registering plugin');

        class ApparatusMessaging extends Snowboard.Singleton {
            construct() {
                console.log('Apparatus Messaging: Constructing');

                this.defaults = {
                    noty: {
                        layout: 'top',
                        theme: 'tailwind',
                        type: 'alert',
                        text: '',
                        dismissQueue: true,
                        template:
                            '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',
                        animation: {
                            open: 'animated fadeIn',
                            close: 'animated fadeOut',
                            easing: 'swing',
                            speed: 500
                        },
                        timeout: 5000, // 5 seconds in milliseconds
                        force: false,
                        modal: false,
                        maxVisible: 5,
                        killer: false,
                        closeWith: ['click'],
                        callback: {
                            onShow: function () {},
                            afterShow: function () {},
                            onClose: function () {},
                            afterClose: function () {},
                            onCloseClick: function () {}
                        },
                        buttons: false
                    }
                };

                this.config = this.deepClone(this.defaults);
                this.initialized = false;
            }

            /**
             * Plugin dependencies
             */
            dependencies() {
                return ['request'];
            }

            /**
             * Snowboard events this plugin listens to
             */
            listens() {
                return {
                    ready: 'onReady',
                };
            }

            /**
             * Called when Snowboard is ready
             */
            onReady() {
                if (this.initialized) {
                    return;
                }

                // Read config from data-messaging-config element
                const configEl = document.querySelector('[data-messaging-config]');

                if (configEl) {
                    const config = this.makeConfig(configEl);
                    this.config = this.deepMerge(this.config, config);
                }

                this.initialized = true;

                // Expose to global for BC
                if (!window.Apparatus) {
                    window.Apparatus = {};
                }
                window.Apparatus.messaging = this;

                console.log('Apparatus Messaging: Initialized, config:', this.config);
            }

            /**
             * Handle Snowboard ajaxErrorMessage event
             * Return false to prevent Winter CMS default alert
             */
            onAjaxErrorMessage(message) {
                console.log('Apparatus Messaging: onAjaxErrorMessage called with message:', message);
                this.handleMessage({ type: 'error', text: message });
                // Return false to prevent Winter CMS default behavior
                return false;
            }

            /**
             * Handle Snowboard ajaxFlashMessages event
             * Return false to prevent Winter CMS default flash display
             */
            onAjaxFlashMessages(messages) {
                console.log('Apparatus Messaging: onAjaxFlashMessages called with messages:', messages);
                // Messages is an object like { success: "Message text", error: "Error text" }
                Object.entries(messages).forEach(([type, message]) => {
                    this.handleFlashMessage(type, message);
                });
                // Return false to prevent Winter CMS default behavior
                return false;
            }

            /**
             * Show a generic Noty message
             */
            handleMessage(options) {
                console.log('Apparatus Messaging: Handling message with options:', options);
                if (typeof Noty === 'undefined') {
                    console.error('Noty library not loaded. Cannot display message:', options);
                    return;
                }

                const notyOptions = this.deepMerge(this.config.noty, options);

                // console.log('Apparatus Messaging - Noty options:', notyOptions);

                new Noty(notyOptions).show();
            }

            /**
             * Show a flash-type message (success, error, info, warning)
             */
            handleFlashMessage(type, message) {
                console.log('Apparatus Messaging: Handling flash message of type', type, 'with message:', message);
                this.handleMessage({
                    type: this.parseFlashMessageType(type),
                    text: message
                });
            }

            /**
             * Map flash type to Noty type
             */
            parseFlashMessageType(flashMessageType) {
                console.log('Apparatus Messaging: Parsing flash message type:', flashMessageType);
                switch (flashMessageType) {
                    case 'info':
                        return 'information';
                    default:
                        return flashMessageType;
                }
            }

            /**
             * Build config from data attributes
             */
            makeConfig(configEl) {
                if (!configEl || !configEl.dataset) {
                    return {};
                }

                const dataset = configEl.dataset;
                const defaults = this.defaults.noty;

                const parseBool = (val, fallback) => {
                    if (val === undefined) return fallback;
                    if (val === 'true' || val === true) return true;
                    if (val === 'false' || val === false) return false;
                    return fallback;
                };

                const parseIntSafe = (val, fallback) => {
                    if (val === undefined) return fallback;
                    const n = parseInt(val, 10);
                    return isNaN(n) ? fallback : n;
                };

                return {
                    noty: {
                        layout: dataset.msgLayout || defaults.layout,
                        theme: dataset.msgTheme || defaults.theme,
                        dismissQueue: parseBool(dataset.msgDismissQueue, defaults.dismissQueue),
                        template: dataset.msgTemplate || defaults.template,
                        animation: {
                            open: dataset.msgAnimationOpen
                                ? this.parseJson(dataset.msgAnimationOpen)
                                : defaults.animation.open,
                            close: dataset.msgAnimationClose
                                ? this.parseJson(dataset.msgAnimationClose)
                                : defaults.animation.close
                        },
                        timeout: parseIntSafe(dataset.msgTimeout, defaults.timeout),
                        force: parseBool(dataset.msgForce, defaults.force),
                        modal: parseBool(dataset.msgModal, defaults.modal),
                        maxVisible: parseIntSafe(dataset.msgMaxVisible, defaults.maxVisible)
                    }
                };
            }

            /**
             * Safe JSON parse
             */
            parseJson(str) {
                try {
                    return JSON.parse(str);
                } catch (e) {
                    return str;
                }
            }

            /**
             * Deep merge two objects
             */
            deepMerge(target, source) {
                const result = this.deepClone(target);

                for (const key in source) {
                    if (!Object.prototype.hasOwnProperty.call(source, key)) {
                        continue;
                    }

                    if (
                        source[key] &&
                        typeof source[key] === 'object' &&
                        !Array.isArray(source[key])
                    ) {
                        result[key] = this.deepMerge(result[key] || {}, source[key]);
                    } else {
                        result[key] = source[key];
                    }
                }

                return result;
            }

            /**
             * Deep clone helper
             */
            deepClone(obj) {
                if (obj === null || typeof obj !== 'object') {
                    return obj;
                }

                if (Array.isArray(obj)) {
                    return obj.map((item) => this.deepClone(item));
                }

                const cloned = {};
                for (const key in obj) {
                    if (Object.prototype.hasOwnProperty.call(obj, key)) {
                        cloned[key] = this.deepClone(obj[key]);
                    }
                }

                return cloned;
            }
        }

        // Register the plugin with Snowboard
        Snowboard.addPlugin('apparatus.messaging', ApparatusMessaging);

        console.log('Apparatus Messaging: Plugin registered');

        // NUCLEAR OPTION: Watch the DOM for Winter CMS flash messages and convert them to Noty
        // Since Snowboard's event system is impossible to intercept, we'll just watch for the DOM changes
        const processedMessages = new Set();
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    // Check if this is a Winter CMS flash message
                    if (node.nodeType === 1 && node.classList && node.classList.contains('flash-message')) {
                        // Extract the message text - use this as a unique identifier
                        const messageText = node.textContent.trim();

                        // Skip if we've already processed this exact message recently
                        if (processedMessages.has(messageText)) {
                            console.log('Apparatus Messaging: Skipping duplicate flash message:', messageText);
                            node.remove();
                            return;
                        }

                        console.log('Apparatus Messaging: Intercepted Winter CMS flash message!', node);

                        // Mark this message as processed
                        processedMessages.add(messageText);

                        // Clear the processed flag after 2 seconds to allow same message again later
                        setTimeout(() => {
                            processedMessages.delete(messageText);
                        }, 2000);

                        // Determine message type
                        let messageType = 'info';
                        if (node.classList.contains('error')) messageType = 'error';
                        else if (node.classList.contains('success')) messageType = 'success';
                        else if (node.classList.contains('warning')) messageType = 'warning';
                        else if (node.classList.contains('info')) messageType = 'info';

                        // Remove the Winter CMS flash message immediately
                        node.remove();

                        // Show our Noty notification instead
                        const messaging = window.Apparatus && window.Apparatus.messaging;
                        if (messaging && typeof Noty !== 'undefined') {
                            console.log('Apparatus Messaging: Converting to Noty:', messageType, messageText);
                            messaging.handleFlashMessage(messageType, messageText);
                        }
                    }
                });
            });
        });

        // Start observing the document body for added flash messages
        observer.observe(document.body, {
            childList: true,
            subtree: false // Only watch direct children of body
        });

        console.log('Apparatus Messaging: DOM observer attached - will intercept Winter CMS flash messages');
    }
})();
