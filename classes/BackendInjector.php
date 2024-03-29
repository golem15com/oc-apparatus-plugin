<?php namespace Golem15\Apparatus\Classes;

use Backend\Classes\Controller;

/**
 * Class BackendInjector
 *
 * @package Golem15\Apparatus\Classes
 */
class BackendInjector
{
    /**
     * @var bool
     */
    protected $useBackendJSInjector = true;

    /**
     * @var array
     */
    protected $jsAssets = [];
    /**
     * @var array
     */
    protected $cssAssets = [];
    /**
     * @var array
     */
    protected $ajaxHandlers = [];

    /**
     * BackendInjector constructor.
     */
    public function __construct()
    {
        Controller::extend(
            function (Controller $controller) {

                foreach ($this->jsAssets as $asset) {
                    if (\is_array($asset['attributes'])) {
                        $asset['attributes']['build'] = 'apparatus-injected';
                    }

                    $controller->addJs($asset['path'], $asset['attributes']);
                }

                foreach ($this->cssAssets as $asset) {
                    if (\is_array($asset['attributes'])) {
                        $asset['attributes']['build'] = 'apparatus-injected';
                    }

                    $controller->addCss($asset['path'], $asset['attributes']);
                }

                foreach ($this->ajaxHandlers as $handler) {
                    $controller->addDynamicMethod($handler['name'], $handler['function'], $handler['extension']);
                }

                if ($this->useBackendJSInjector) {
                    $controller->addJs('/plugins/golem15/apparatus/assets/js/framework.validation.js', 'apparatus');
                }
            }
        );
    }

    /**
     * @param string $path
     * @param array  $attributes
     */
    public function addJs(string $path, array $attributes = []): void
    {
        $this->jsAssets[] = ['path' => $path, 'attributes' => $attributes];
    }

    /**
     * @param string $path
     * @param array  $attributes
     */
    public function addCss(string $path, array $attributes = []): void
    {
        $this->cssAssets[] = ['path' => $path, 'attributes' => $attributes];
    }

    /**
     * @param string      $name
     * @param callable    $handler
     * @param string|null $extension
     */
    public function addAjaxHandler(string $name, callable $handler, ?string $extension = null)
    {
        $this->ajaxHandlers[] = ['name' => $name, 'function' => $handler, 'extension' => $extension];
    }
}
