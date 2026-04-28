((Snowboard) => {
    'use strict';

    class G15Sensitive extends Snowboard.PluginBase {
        construct(element) {
            this.element = element;
            this.config = this.snowboard.dataConfig(this, element);
            this.clean = Boolean(element.dataset.clean);
            this.hidden = true;

            this.input = element.querySelector('[data-input]');
            this.toggle = element.querySelector('[data-toggle]');
            this.icon = element.querySelector('[data-icon]');
            this.loader = element.querySelector('[data-loader]');
            this.copy = element.querySelector('[data-copy]');

            this.events = {
                input: () => this.onInput(),
                toggle: () => this.onToggle(),
                tabChange: () => this.onTabChange(),
                copy: () => this.onCopy(),
            };

            this.attachEvents();
        }

        defaults() {
            return {
                readOnly: false,
                disabled: false,
                eventHandler: null,
                hideOnTabChange: false,
            };
        }

        attachEvents() {
            this.input.addEventListener('keydown', this.events.input);
            this.toggle.addEventListener('click', this.events.toggle);

            if (this.config.get('hideOnTabChange')) {
                document.addEventListener('visibilitychange', this.events.tabChange);
            }

            if (this.copy) {
                this.copy.addEventListener('click', this.events.copy);
            }
        }

        destruct() {
            this.input.removeEventListener('keydown', this.events.input);
            this.toggle.removeEventListener('click', this.events.toggle);

            if (this.config.get('hideOnTabChange')) {
                document.removeEventListener('visibilitychange', this.events.tabChange);
            }

            if (this.copy) {
                this.copy.removeEventListener('click', this.events.copy);
            }

            this.input = null;
            this.toggle = null;
            this.icon = null;
            this.loader = null;

            super.destruct();
        }

        onInput() {
            if (this.clean) {
                this.clean = false;
                this.input.value = '';
            }
        }

        onToggle() {
            if (this.input.value !== '' && this.clean) {
                this.reveal();
            } else {
                this.toggleVisibility();
            }
        }

        onTabChange() {
            if (document.hidden && !this.hidden) {
                this.toggleVisibility();
            }
        }

        onCopy() {
            const promise = new Promise((resolve, reject) => {
                if (this.input.value !== '' && this.clean) {
                    this.reveal().then(resolve, reject);
                } else {
                    resolve();
                }
            });

            promise.then(
                () => {
                    const wasHidden = this.hidden;

                    if (this.hidden) {
                        this.toggleVisibility();
                    }

                    this.input.focus();
                    this.input.select();

                    try {
                        const blob = new Blob([this.input.value], { type: 'text/plain' });
                        const item = new ClipboardItem({ 'text/plain': blob });
                        navigator.clipboard.write([item]);
                    } catch (error) {
                        this.snowboard.error(`Clipboard API not supported - ${error}`);
                    }

                    this.input.blur();
                    if (wasHidden) {
                        this.toggleVisibility();
                    }
                },
                (error) => {
                    this.snowboard.error(`Unable to retrieve hidden value - ${error}`);
                }
            );
        }

        toggleVisibility() {
            this.input.setAttribute('type', this.hidden ? 'text' : 'password');
            this.icon.classList.toggle('icon-eye');
            this.icon.classList.toggle('icon-eye-slash');
            this.hidden = !this.hidden;
        }

        reveal() {
            return new Promise((resolve, reject) => {
                this.icon.style.visibility = 'hidden';
                this.loader.classList.remove('hide');

                this.snowboard.request(this.input, this.config.get('eventHandler'), {
                    success: (data) => {
                        this.input.value = data.value;
                        this.clean = false;
                        this.icon.style.visibility = 'visible';
                        this.loader.classList.add('hide');
                        this.toggleVisibility();
                        resolve();
                    },
                    error: (error) => {
                        this.icon.style.visibility = 'visible';
                        this.loader.classList.add('hide');
                        reject(new Error(error));
                    }
                });
            });
        }
    }

    Snowboard.addPlugin('backend.formwidget.g15sensitive', G15Sensitive);
    Snowboard['backend.ui.widgethandler']().register('g15sensitive', 'backend.formwidget.g15sensitive');
})(window.Snowboard);
