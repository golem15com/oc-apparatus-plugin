/**
 * Apparatus Confirm Modal - Snowboard Plugin
 *
 * Intercepts Snowboard AJAX confirmation dialogs and displays a custom modal
 * instead of the browser's native confirm() dialog.
 */

(function() {
    if (typeof Snowboard === 'undefined') {
        console.error('Apparatus ConfirmModal: Snowboard not available');
        return;
    }

    // Wait for Snowboard to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('Apparatus ConfirmModal: Registering plugin');

        /**
         * Apparatus Confirm Modal Plugin
         * Extends Snowboard.Singleton to properly integrate with event system
         */
        class ApparatusConfirmModal extends Snowboard.Singleton {
            /**
             * Define which events this plugin listens to
             */
            listens() {
                console.log('Apparatus ConfirmModal: listens() called');
                return {
                    ajaxConfirmMessage: 'handleConfirm'
                };
            }

            /**
             * Handle confirmation requests
             * @param {string} message - The confirmation message
             * @param {Object} request - The Snowboard request object
             * @returns {Promise} - Resolves if confirmed, rejects if cancelled
             */
            handleConfirm(message, request) {
                console.log('Apparatus ConfirmModal: Intercepted confirmation request:', message);

                return new Promise((resolve, reject) => {
                    // Get modal elements
                    const modal = document.getElementById('apparatus-confirm-modal');
                    const messageEl = document.getElementById('apparatus-confirm-message');
                    const cancelBtn = document.getElementById('apparatus-confirm-cancel');
                    const okBtn = document.getElementById('apparatus-confirm-ok');

                    if (!modal || !messageEl || !cancelBtn || !okBtn) {
                        console.error('Apparatus ConfirmModal: Required elements not found, falling back to browser confirm');
                        // Fallback to browser confirm
                        if (window.confirm(message)) {
                            resolve();
                        } else {
                            reject();
                        }
                        return;
                    }

                    // Set message
                    messageEl.textContent = message;

                    // Show modal
                    modal.style.display = 'flex';

                    // Focus OK button for accessibility
                    setTimeout(() => {
                        okBtn.focus();
                    }, 100);

                    // Create event handlers
                    const handleConfirmClick = () => {
                        cleanup();
                        modal.style.display = 'none';
                        resolve(); // User confirmed
                    };

                    const handleCancelClick = () => {
                        cleanup();
                        modal.style.display = 'none';
                        reject(); // User cancelled
                    };

                    const handleBackdropClick = (e) => {
                        if (e.target === modal) {
                            handleCancelClick();
                        }
                    };

                    const handleKeydown = (e) => {
                        if (e.key === 'Escape') {
                            handleCancelClick();
                        } else if (e.key === 'Enter') {
                            handleConfirmClick();
                        }
                    };

                    // Add event listeners
                    okBtn.addEventListener('click', handleConfirmClick);
                    cancelBtn.addEventListener('click', handleCancelClick);
                    modal.addEventListener('click', handleBackdropClick);
                    document.addEventListener('keydown', handleKeydown);

                    // Cleanup function to remove listeners
                    function cleanup() {
                        okBtn.removeEventListener('click', handleConfirmClick);
                        cancelBtn.removeEventListener('click', handleCancelClick);
                        modal.removeEventListener('click', handleBackdropClick);
                        document.removeEventListener('keydown', handleKeydown);
                    }
                });
            }
        }

        // Register the plugin with Snowboard
        Snowboard.addPlugin('apparatusConfirmModal', ApparatusConfirmModal);
        console.log('Apparatus ConfirmModal: Plugin registered');
    }
})();
