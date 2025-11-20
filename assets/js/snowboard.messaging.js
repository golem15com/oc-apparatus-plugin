/**
 * Apparatus Messaging - Snowboard Plugin
 *
 * Provides notification functionality using Noty library
 * Replaces jQuery-based framework.messaging.js with Snowboard API
 */

Snowboard.addPlugin('apparatus.messaging', class ApparatusMessaging {

    /**
     * Constructor
     */
    construct() {
        this.defaults = {
            noty: {
                layout: 'top',
                theme: 'bootstrap-v4', // or 'relax'
                type: 'alert',
                text: '', // can be html or string
                dismissQueue: true, // If you want to use queue feature set this true
                template: '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',
                animation: {
                    open: {},
                    close: {},
                    easing: 'swing',
                    speed: 1
                },
                timeout: false, // delay for closing event. Set false for sticky notifications
                force: false, // adds notification to the beginning of queue when set to true
                modal: false,
                maxVisible: 5000, // you can set max visible notification for dismissQueue true option,
                killer: false, // for close all notifications before show
                closeWith: ['click'], // ['click', 'button', 'hover', 'backdrop'] // backdrop click will close all notifications
                callback: {
                    onShow: function () {},
                    afterShow: function () {},
                    onClose: function () {},
                    afterClose: function () {},
                    onCloseClick: function () {}
                },
                buttons: false // an array of buttons
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
     * Listeners for Snowboard events
     */
    listens() {
        return {
            ready: 'onReady',
            ajaxErrorMessage: 'onAjaxError'
        };
    }

    /**
     * Initialize when Snowboard is ready
     */
    onReady() {
        if (this.initialized) {
            return;
        }

        // Find config element
        const configEl = document.querySelector('[data-messaging-config]');

        if (configEl) {
            const config = this.makeConfig(configEl);
            this.config = this.deepMerge(this.config, config);
        }

        // Listen for AJAX error messages
        this.snowboard.globalEvent('ajaxErrorMessage', (message) => {
            this.handleAjaxError(message);
        });

        this.initialized = true;

        // Expose to global scope for backward compatibility
        if (!window.Apparatus) {
            window.Apparatus = {};
        }
        window.Apparatus.messaging = this;
    }

    /**
     * Handle AJAX error messages
     */
    onAjaxError(message) {
        this.handleAjaxError(message);
    }

    /**
     * Handle AJAX error messages
     */
    handleAjaxError(message) {
        // Try to parse X_OCTOBER_ERROR_MESSAGE from JSON response
        try {
            const json = JSON.parse(message);
            if (json.X_OCTOBER_ERROR_MESSAGE) {
                message = json.X_OCTOBER_ERROR_MESSAGE;
            }
        } catch (e) {
            // Message is already plain text, use as-is
        }

        this.handleMessage({ type: 'error', text: message });
    }

    /**
     * Display a notification message
     *
     * @param {Object} options - Noty options (type, text, etc.)
     */
    handleMessage(options) {
        if (typeof Noty === 'undefined') {
            console.error('Noty library not loaded. Cannot display message:', options);
            return;
        }

        const notyOptions = this.deepMerge(this.config.noty, options);

        console.log('Apparatus Messaging - Noty options:', notyOptions);

        new Noty(notyOptions).show();
    }

    /**
     * Display a flash message
     *
     * @param {String} type - Message type (success, error, info, warning)
     * @param {String} message - Message text
     */
    handleFlashMessage(type, message) {
        this.handleMessage({
            type: this.parseFlashMessageType(type),
            text: message
        });
    }

    /**
     * Parse flash message type to Noty type
     *
     * @param {String} flashMessageType
     * @returns {String}
     */
    parseFlashMessageType(flashMessageType) {
        switch (flashMessageType) {
            case 'info':
                return 'information';
            default:
                return flashMessageType;
        }
    }

    /**
     * Build config from data attributes
     *
     * @param {HTMLElement} configEl
     * @returns {Object}
     */
    makeConfig(configEl) {
        if (!configEl || !configEl.dataset) {
            return {};
        }

        const dataset = configEl.dataset;
        const defaults = this.defaults.noty;

        return {
            noty: {
                layout: dataset.msgLayout || defaults.layout,
                theme: dataset.msgTheme || defaults.theme,
                dismissQueue: dataset.msgDismissQueue !== undefined ? dataset.msgDismissQueue : defaults.dismissQueue,
                template: dataset.msgTemplate || defaults.template,
                animation: {
                    open: dataset.msgAnimationOpen ? this.parseJson(dataset.msgAnimationOpen) : defaults.animation.open,
                    close: dataset.msgAnimationClose ? this.parseJson(dataset.msgAnimationClose) : defaults.animation.close
                },
                timeout: dataset.msgTimeout !== undefined ? parseInt(dataset.msgTimeout, 10) : defaults.timeout,
                force: dataset.msgForce !== undefined ? dataset.msgForce : defaults.force,
                modal: dataset.msgModal !== undefined ? dataset.msgModal : defaults.modal,
                maxVisible: dataset.msgMaxVisible !== undefined ? parseInt(dataset.msgMaxVisible, 10) : defaults.maxVisible
            }
        };
    }

    /**
     * Parse JSON string safely
     *
     * @param {String} str
     * @returns {*}
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
     *
     * @param {Object} target
     * @param {Object} source
     * @returns {Object}
     */
    deepMerge(target, source) {
        const result = this.deepClone(target);

        for (const key in source) {
            if (source.hasOwnProperty(key)) {
                if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                    result[key] = this.deepMerge(result[key] || {}, source[key]);
                } else {
                    result[key] = source[key];
                }
            }
        }

        return result;
    }

    /**
     * Deep clone an object
     *
     * @param {Object} obj
     * @returns {Object}
     */
    deepClone(obj) {
        if (obj === null || typeof obj !== 'object') {
            return obj;
        }

        if (Array.isArray(obj)) {
            return obj.map(item => this.deepClone(item));
        }

        const cloned = {};
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                cloned[key] = this.deepClone(obj[key]);
            }
        }

        return cloned;
    }
});