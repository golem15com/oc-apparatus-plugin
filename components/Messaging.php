<?php namespace Keios\Apparatus\Components;

use Cms\Classes\ComponentBase;
use Keios\Apparatus\Models\Settings;

/**
 * Class Messaging
 *
 * @package Keios\Apparatus\Components
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
                'title'       => 'keios.apparatus::lang.strings.inject_main',
                'description' => 'keios.apparatus::lang.strings.inject_main_desc',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'injectNoty' => [
                'title'       => 'keios.apparatus::lang.strings.inject_noty',
                'description' => 'keios.apparatus::lang.strings.inject_noty_desc',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'injectAnimate' => [
                'title'       => 'keios.apparatus::lang.strings.inject_animate',
                'description' => 'keios.apparatus::lang.strings.inject_animate_desc',
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
            $this->addCss('/plugins/keios/apparatus/assets/css/animate.min.css');
        }
        if($this->property('injectNoty')) {
            $this->addJs('/plugins/keios/apparatus/assets/js/noty/noty.min.js');
            $this->addCss('/plugins/keios/apparatus/assets/js/noty/noty.css');
        }
        if($this->property('injectMain')) {
            $this->addJs('/plugins/keios/apparatus/assets/js/framework.messaging.js');
        }

        $settings = Settings::instance()->value;

        if (!\is_array($settings)) {
            return;
        }

        $this->layout = $settings['layout'];
        $this->openAnimation = $settings['openAnimation'];
        $this->closeAnimation = $settings['closeAnimation'];
        $this->theme = $settings['theme'];
        $this->template = $settings['template'];
        $this->timeout = $settings['timeout'] * 1000;
        $this->dismissQueue = $settings['dismissQueue'];
        $this->force = $settings['force'];
        $this->modal = $settings['modal'];
        $this->maxVisible = $settings['maxVisible'] * 1000;

        $this->addCss('/plugins/keios/apparatus/assets/js/noty/themes/' . $this->theme . '.css');
    }

}
