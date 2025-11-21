<?php namespace Golem15\Apparatus;

use Backend;
use Backend\Classes\Controller;
use Cms\Classes\ComponentBase;
use Event;
use Flash;
use Golem15\Apparatus\Console\FakeJob;
use Golem15\Apparatus\Console\SaneGitModules;
use Illuminate\Foundation\AliasLoader;
use Golem15\Apparatus\Classes\BackendInjector;
use Golem15\Apparatus\Classes\DependencyInjector;
use Golem15\Apparatus\Classes\HumanDateExtension;
use Golem15\Apparatus\Classes\RouteResolver;
use Golem15\Apparatus\Console\Optimize;
use Golem15\Apparatus\FormWidgets\ListToggle;
use Golem15\Apparatus\Console\QueueClearCommand;
use Golem15\Apparatus\Classes\LaravelQueueClearServiceProvider;
use System\Classes\PluginBase;
use Keios\LaravelApparatus\LaravelApparatusServiceProvider;
use October\Rain\Translation\Translator;
use Golem15\Apparatus\FormWidgets\KnobWidget;
use Intervention\Image\ImageServiceProvider;

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
            'description' => 'golem15.apparatus::lang.labels.pluginName',
            'author'      => 'Golem15',
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
            Components\ConfirmModal::class => 'confirmModal',
        ];
    }

    /**
     * @return array
     */
    public function registerPermissions(): array
    {
        return [
            'golem15.apparatus.access_settings' => [
                'tab'   => 'golem15.apparatus::lang.permissions.tab',
                'label' => 'golem15.apparatus::lang.permissions.access_settings',
            ],
            'golem15.apparatus.access_jobs'     => [
                'tab'   => 'golem15.apparatus::lang.permissions.tab',
                'label' => 'golem15.apparatus::lang.permissions.access_jobs',
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
                'label'       => 'golem15.apparatus::lang.labels.apparatus',
                'url'         => Backend::url('golem15/apparatus/jobs'),
                'icon'        => 'icon-gears',
                'iconSvg'     => 'plugins/golem15/apparatus/assets/img/gear.svg',
                'order'       => 700,
                'permissions' => ['golem15.apparatus.*'],
                'sideMenu'    => [
                    'jobs' => [
                        'label'       => 'golem15.apparatus::lang.labels.jobs',
                        'icon'        => 'icon-gears',
                        'url'         => Backend::url('golem15/apparatus/jobs'),
                        'permissions' => ['golem15.apparatus.access_jobs'],
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
                'label'       => 'golem15.apparatus::lang.settings.messaging-label',
                'description' => 'golem15.apparatus::lang.settings.messaging-description',
                'category'    => 'Apparatus',
                'icon'        => 'icon-globe',
                'class'       => Models\Settings::class,
                'permissions' => ['golem15.apparatus.access_settings'],
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
        $this->app->register(ImageServiceProvider::class);
        $this->commands(
            [
                Optimize::class,
                QueueClearCommand::class,
                SaneGitModules::class,
                FakeJob::class
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
                'label' => 'golem15.apparatus::lang.labels.knobFormWidget',
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
        $injector->addCss('/plugins/golem15/apparatus/assets/css/animate.css');

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
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'ucfirst' => 'ucfirst',
                'human_date' => [$this, 'humanDateFilter'],

            ]
        ];
    }

    public function humanDateFilter($dateString) {
        return (new HumanDateExtension())->humanDateFilter($dateString);
    }
}
