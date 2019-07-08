;
(function ($, window, document, undefined) {

    "use strict";

    if (!$.apparatus) {
        $.apparatus = {};
    }

    var defaults = {
        messageClass: 'validation-message',
        debug: true
    };

    function ValidationHelper() {
        var self = this;
        this.errorFieldsCache = {};
        this.$window = $(window);

        var configEl = $('[data-validation-config]');

        if (configEl.length) {
            configEl = configEl.first();
        }

        var config = this.makeConfig(configEl);

        this.config = $.extend(true, {}, defaults, config);

        this.$window.on('oc.beforeRequest', function () {
            self.clearValidationErrors();
        });

        this.$window.on('ajaxErrorMessage', function (event, context, textString, jqXHR) {
            event.preventDefault();
        });

        this.$window.on('ajaxInvalidField', function (event, fieldElement, fieldName, fieldMessages, isFirstInvalidField) {
            self.handleInvalidField(fieldElement, fieldName, fieldMessages, isFirstInvalidField);
        });
    }

    ValidationHelper.prototype.makeConfig = function (configEl) {
        if (!configEl.data) {
            return {};
        }

        return {
            messageClass: configEl.data('message-class') ? configEl.data('message-class') : defaults.messageClass,
            debug: configEl.data('debug')
        };
    };

    ValidationHelper.prototype.clearValidationErrors = function () {
        for (var field in this.errorFieldsCache) {
            if (this.errorFieldsCache.hasOwnProperty(field)) {
                this.errorFieldsCache[field].empty();
            }
        }
    };

    ValidationHelper.prototype.getErrorContainer = function (fieldName, refresh) {
        if (this.errorFieldsCache.hasOwnProperty(fieldName) && !refresh) {
            return this.errorFieldsCache[fieldName];
        } else {
            this.errorFieldsCache[fieldName] = $('#' + fieldName + '_validation');
            return this.errorFieldsCache[fieldName];
        }
    };

    ValidationHelper.prototype.handleInvalidField = function (fieldElement, fieldName, fieldMessages, isFirstInvalidField) {
        var container = this.getErrorContainer(fieldName);
        var self = this;

        fieldMessages.forEach(function (message) {
            container.append('<p class="' + self.config.messageClass + '">' + message + '</p>');
        });

        if (isFirstInvalidField) {
            fieldElement.focus();
        }
    };

    $.apparatus.validation = new ValidationHelper();

})(jQuery, window, document);
