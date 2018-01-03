<?php namespace Keios\Apparatus;

use Backend;
use Cms\Classes\ComponentBase;
use Illuminate\Foundation\AliasLoader;
use Keios\Apparatus\Classes\BackendInjector;
use Keios\Apparatus\Classes\DependencyInjector;
use Keios\Apparatus\Classes\RouteResolver;
use Keios\Apparatus\Console\Optimize;
use System\Classes\PluginBase;
use Keios\LaravelApparatus\LaravelApparatusServiceProvider;
use October\Rain\Translation\Translator;

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
            'icon'        => 'icon-cogs',
        ];
    }

    /**
     * @return array
     */
    public function registerComponents()
    {
        return [
            Components\Messaging::class => 'apparatusFlashMessages',
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
            'keios.apparatus.access_jobs'     => [
                'tab'   => 'keios.apparatus::lang.permissions.tab',
                'label' => 'keios.apparatus::lang.permissions.access_jobs',
            ],
        ];
    }

    public function registerNavigation(): array
    {
        return [
            'apparatus' => [
                'label'       => 'keios.apparatus::lang.labels.jobs',
                'url'         => Backend::url('keios/apparatus/jobs'),
                'icon'        => 'icon-gears',
                'iconSvg'     => 'plugins/keios/apparatus/assets/img/gear.svg',
                'order'       => 500,
                'permissions' => ['keios.apparatus.*'],
                'sideMenu'    => [
                    'jobs' => [
                        'label'       => 'keios.apparatus::lang.labels.jobs',
                        'icon'        => 'icon-gears',
                        'url'         => Backend::url('keios/apparatus/jobs'),
                        'permissions' => ['keios.apparatus.access_jobs'],
                    ],
                ],
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
                'class'       => Models\Settings::class,
                'permissions' => ['keios.apparatus.access_settings'],
                'order'       => 500,
                'keywords'    => 'messages flash notifications',
            ],
        ];
    }

    /**
     * Plugin register method
     */
    public function register()
    {
        $this->commands(
            [
                Optimize::class,
            ]
        );

        $this->app->register(LaravelApparatusServiceProvider::class);

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

        $this->app->when(Classes\TranslApiController::class)
            ->needs(Translator::class)
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
        $aliasLoader->alias('Resolver', Facades\Resolver::class);

        $injector = $this->app->make('apparatus.backend.injector');
        $injector->addCss('/plugins/keios/apparatus/assets/css/animate.css');
    }

}
