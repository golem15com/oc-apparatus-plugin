<?php namespace Keios\Apparatus;

use Backend;
use Backend\Classes\Controller;
use Cms\Classes\ComponentBase;
use Event;
use Flash;
use Illuminate\Foundation\AliasLoader;
use Keios\Apparatus\Classes\BackendInjector;
use Keios\Apparatus\Classes\DependencyInjector;
use Keios\Apparatus\Classes\RouteResolver;
use Keios\Apparatus\Console\Optimize;
use Keios\Apparatus\FormWidgets\ListToggle;
use Keios\Apparatus\Console\QueueClearCommand;
use Keios\Apparatus\Classes\LaravelQueueClearServiceProvider;
use System\Classes\PluginBase;
use Keios\LaravelApparatus\LaravelApparatusServiceProvider;
use October\Rain\Translation\Translator;
use Keios\Apparatus\FormWidgets\KnobWidget;

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
    public function pluginDetails(): array
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
    public function registerComponents(): array
    {
        return [
            Components\Messaging::class => 'apparatusFlashMessages',
        ];
    }

    /**
     * @return array
     */
    public function registerPermissions(): array
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

    /**
     * @return array
     */
    public function registerNavigation(): array
    {
        return [
            'apparatus' => [
                'label'       => 'keios.apparatus::lang.labels.apparatus',
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
    public function registerSettings(): array
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
    public function register(): void
    {
        $this->app->register(LaravelQueueClearServiceProvider::class);
        $this->commands(
            [
                Optimize::class,
                QueueClearCommand::class,
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
     * @return array
     */
    public function registerListColumnTypes(): array
    {
        return [
            'listtoggle' => [ListToggle::class, 'render'],
        ];
    }

    /**
     * @return array
     */
    public function registerFormWidgets()
    {
        return [
            KnobWidget::class => [
                'label' => 'keios.apparatus::lang.labels.knobFormWidget',
                'code' => 'knob'
            ]
        ];
    }

    /**
     * Plugin boot method
     * @throws \ApplicationException
     */
    public function boot(): void
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

        Event::listen(
            'backend.list.extendColumns',
            function ($widget) {
                foreach ($widget->config->columns as $name => $config) {
                    if (empty($config['type']) || $config['type'] !== 'listtoggle') {
                        continue;
                    }
                    // Store field config here, before that unofficial fields was removed
                    ListToggle::storeFieldConfig($name, $config);
                    $column = [
                        'clickable' => false,
                        'type'      => 'listtoggle',
                    ];
                    if (isset($config['label'])) {
                        $column['label'] = $config['label'];
                    }
                    // Set this column not clickable
                    // if other column with same field name exists configs are merged
                    $widget->addColumns(
                        [
                            $name => $column,
                        ]
                    );
                }
            }
        );
        /**
         * Switch a boolean value of a model field
         * @return void
         */
        Controller::extend(
            function ($controller) {
                $controller->addDynamicMethod(
                    'index_onSwitchInetisListField',
                    function () use ($controller) {
                        $field = post('field');
                        $id = post('id');
                        $modelClass = post('model');
                        if (empty($field) || empty($id) || empty($modelClass)) {
                            Flash::error('Following parameters are required : id, field, model');

                            return null;
                        }
                        $model = new $modelClass;
                        $item = $model::find($id);
                        $item->{$field} = !$item->{$field};
                        $item->save();

                        return $controller->listRefresh($controller->primaryDefinition);
                    }
                );
            }
        );
    }

}
