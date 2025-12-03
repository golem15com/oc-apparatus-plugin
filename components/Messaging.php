<?php namespace Golem15\Apparatus\Components;

use Cms\Classes\ComponentBase;
use Golem15\Apparatus\Models\Settings;

/**
 * Class Messaging
 *
 * @package Golem15\Apparatus\Components
 */
class Messaging extends ComponentBase
{
    public $layout;
    public $openAnimation;
    public $closeAnimation;
    public $theme;
    public $template;
    public $timeout;
    public $dismissQueue;
    public $force;
    public $modal;
    public $maxVisible;

    /**
     * @return array
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Apparatus Messaging',
            'description' => 'Provides Apparatus Messaging functionality'
        ];
    }

    public function defineProperties(): array
    {

        return [
            'injectMain' => [
                'title'       => 'golem15.apparatus::lang.strings.inject_main',
                'description' => 'golem15.apparatus::lang.strings.inject_main_desc',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'snowboardMode' => [
                'title'       => 'Snowboard Mode',
                'description' => 'Use Snowboard instead of jQuery (Winter CMS)',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'injectNoty' => [
                'title'       => 'golem15.apparatus::lang.strings.inject_noty',
                'description' => 'golem15.apparatus::lang.strings.inject_noty_desc',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'injectAnimate' => [
                'title'       => 'golem15.apparatus::lang.strings.inject_animate',
                'description' => 'golem15.apparatus::lang.strings.inject_animate_desc',
                'type'        => 'checkbox',
                'default'     => true,
            ]
        ];
    }

    /**
     * Component onRun method
     */
    public function onRun(): void
    {
        if($this->property('injectAnimate')) {
            $this->addCss('/plugins/golem15/apparatus/assets/css/animate.min.css');
        }
        if($this->property('injectNoty')) {
            $this->addJs('/plugins/golem15/apparatus/assets/js/noty/noty.min.js');
            $this->addCss('/plugins/golem15/apparatus/assets/js/noty/noty.css');
        }
        if($this->property('injectMain')) {
            if($this->property('snowboardMode')) {
                $this->addJs('/plugins/golem15/apparatus/assets/js/snowboard.messaging.js');
            } else {
                $this->addJs('/plugins/golem15/apparatus/assets/js/framework.messaging.js');
            }
        }

        $settings = Settings::instance()->value;

        // Provide fallback defaults if settings are not configured
        if (!\is_array($settings) || empty($settings)) {
            $settings = [
                'layout' => 'topRight',
                'theme' => 'tailwind',
                'openAnimation' => 'animated fadeIn',
                'closeAnimation' => 'animated fadeOut',
                'template' => null,
                'timeout' => 5,
                'dismissQueue' => true,
                'force' => false,
                'modal' => false,
                'maxVisible' => 5,
            ];
        }

        $this->layout = $settings['layout'];
        $this->openAnimation = $settings['openAnimation'];
        $this->closeAnimation = $settings['closeAnimation'];
        $this->theme = $settings['theme'] ?? 'tailwind'; // Default to tailwind
        $this->template = $settings['template'];
        $this->timeout = $settings['timeout'] * 1000;
        $this->dismissQueue = $settings['dismissQueue'];
        $this->force = $settings['force'];
        $this->modal = $settings['modal'];
        $this->maxVisible = $settings['maxVisible'] * 1000;

        $this->addCss('/plugins/golem15/apparatus/assets/js/noty/themes/' . $this->theme . '.css');
    }

}
