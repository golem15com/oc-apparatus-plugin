<?php namespace Keios\Apparatus;

use Cms\Classes\ComponentBase;
use Illuminate\Foundation\AliasLoader;
use Keios\Apparatus\Classes\BackendInjector;
use Keios\Apparatus\Classes\DependencyInjector;
use Keios\Apparatus\Classes\RouteResolver;
use System\Classes\PluginBase;

/**
 * Apparatus Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Apparatus',
            'description' => 'keios.apparatus::lang.labels.pluginName',
            'author'      => 'Keios',
            'icon'        => 'icon-cogs'
        ];
    }

    /**
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Keios\Apparatus\Components\Messaging' => 'apparatusFlashMessages'
        ];
    }

    /**
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'keios.apparatus.access_settings' => [
                'tab'   => 'keios.apparatus::lang.permissions.tab',
                'label' => 'keios.apparatus::lang.permissions.access_settings',
            ],
        ];
    }

    /**
     * @return array
     */
    public function registerSettings()
    {
        return [
            'messaging' => [
                'label'       => 'keios.apparatus::lang.settings.messaging-label',
                'description' => 'keios.apparatus::lang.settings.messaging-description',
                'category'    => 'Apparatus',
                'icon'        => 'icon-globe',
                'class'       => '\Keios\Apparatus\Models\Settings',
                'permissions' => ['keios.apparatus.access_settings'],
                'order'       => 500,
                'keywords'    => 'messages flash notifications'
            ]
        ];
    }

    /**
     * Plugin register method
     */
    public function register()
    {
        
        $this->app->register('Keios\LaravelApparatus\LaravelApparatusServiceProvider');

        $this->app->singleton(
            'apparatus.route.resolver',
            function () {
                return new RouteResolver($this->app['config'], $this->app['log']);
            }
        );
        
        $this->app->singleton(
            'apparatus.backend.injector',
            function () {
                return new BackendInjector();
            }
        );

        $this->app->singleton(
            'apparatus.dependencyInjector',
            function () {
                return new DependencyInjector($this->app);
            }
        );
    }

    /**
     * Plugin boot method
     */
    public function boot()
    {
        $translator = $this->app->make('translator');

        $this->app->when('Keios\Apparatus\Classes\TranslApiController')
            ->needs('October\Rain\Translation\Translator')
            ->give(
                function () use ($translator) {
                    return $translator;
                }
            );

        $this->app->make('events')->listen(
            'cms.page.initComponents',
            function ($controller) {
                foreach ($controller->vars as $variable) {
                    if ($variable instanceof ComponentBase) {
                        $this->app->make('apparatus.dependencyInjector')->injectDependencies($variable);
                    }
                }
            }
        );

        $aliasLoader = AliasLoader::getInstance();
        $aliasLoader->alias('Resolver', 'Keios\Apparatus\Facades\Resolver');

        $injector = $this->app->make('apparatus.backend.injector');
        $injector->addCss('/plugins/keios/apparatus/assets/css/animate.css');
    }

}
