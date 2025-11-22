<?php

namespace Golem15\Apparatus\Components;

use Cms\Classes\ComponentBase;

/**
 * ConfirmModal Component
 *
 * Provides a custom styled confirmation modal that replaces browser's native confirm() dialog.
 * Automatically intercepts all Snowboard AJAX requests with data-request-confirm attribute.
 *
 * Usage: {% component 'confirmModal' %}
 */
class ConfirmModal extends ComponentBase
{
    /**
     * @var string Component name
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Confirm Modal',
            'description' => 'Global custom confirmation dialog for AJAX requests'
        ];
    }

    /**
     * Component initialization
     */
    public function onRun()
    {
        // Add JavaScript for Snowboard event handler
        $this->addJs('/plugins/golem15/apparatus/assets/js/snowboard.confirm-handler.js');
    }
}
