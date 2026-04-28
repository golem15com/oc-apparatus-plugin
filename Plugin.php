<?php namespace Golem15\Apparatus;

use Backend;
use Backend\Classes\Controller;
use Cms\Classes\ComponentBase;
use Event;
use Flash;
use Golem15\Apparatus\Console\FakeJob;
use Golem15\Apparatus\Console\SaneGitModules;
use Golem15\Apparatus\Console\MailExportCommand;
use Golem15\Apparatus\Console\MailImportCommand;
use Golem15\Apparatus\Console\MailResetCommand;
use Illuminate\Foundation\AliasLoader;
use Golem15\Apparatus\Classes\BackendInjector;
use Golem15\Apparatus\FormWidgets\Sensitive;
use Golem15\Apparatus\Classes\DependencyInjector;
use Golem15\Apparatus\Classes\HumanDateExtension;
use Golem15\Apparatus\Classes\RouteResolver;
use Golem15\Apparatus\Console\Optimize;
use Golem15\Apparatus\FormWidgets\ListToggle;
use Golem15\Apparatus\Console\QueueClearCommand;
use Golem15\Apparatus\Classes\LaravelQueueClearServiceProvider;
use Golem15\Apparatus\Models\PersonalApiToken;
use System\Classes\PluginBase;
use Keios\LaravelApparatus\LaravelApparatusServiceProvider;
use October\Rain\Translation\Translator;
use Golem15\Apparatus\FormWidgets\KnobWidget;
// use Intervention\Image\ImageServiceProvider; // Not needed in Intervention Image v3 (auto-discovered)
use Winter\Translate\Classes\ThemeScanner;
use Winter\Translate\Models\Message;

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
            Components\InfiniteScroll::class => 'infiniteScroll',
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
        // $this->app->register(ImageServiceProvider::class); // Not needed in Intervention Image v3 (auto-discovered)
        $this->commands(
            [
                Optimize::class,
                QueueClearCommand::class,
                SaneGitModules::class,
                FakeJob::class,
                MailExportCommand::class,
                MailImportCommand::class,
                MailResetCommand::class,
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

        \Event::listen('winter.translate.themeScanner.afterScan', function (ThemeScanner $scanner) {
            $messages = [];
            // scan <root>/plugins/*/*/components/*/*.htm for messages for all plugins in system
            $pluginPath = plugins_path();
            $componentPath = $pluginPath . '/*/*/components/*/*.htm';
            $componentFiles = glob($componentPath);
            foreach ($componentFiles as $file) {
                $content = file_get_contents($file);
                $messages = array_merge($messages, $scanner->parseContent($content));
            }

            Message::importMessages($messages);
        });
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
            ],
            Sensitive::class => [
                'label' => 'golem15.apparatus::lang.labels.sensitiveFormWidget',
                'code' => 'g15sensitive'
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
                            throw new \InvalidArgumentException('Required parameters: id, field, model');
                        }

                        // D-01: Verify controller has ListController behavior
                        if (!$controller->isClassExtendedWith(\Backend\Behaviors\ListController::class)) {
                            throw new \InvalidArgumentException('Controller does not implement ListController');
                        }

                        // D-02: Enforce controller's $requiredPermissions
                        $requiredPermissions = $controller->requiredPermissions ?? [];
                        if (!empty($requiredPermissions)) {
                            $backendUser = \BackendAuth::getUser();
                            if (!$backendUser || !$backendUser->hasAccess($requiredPermissions)) {
                                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Insufficient permissions');
                            }
                        }

                        // D-01: Validate model class against controller's list config
                        $listConfig = $controller->listGetConfig();
                        $allowedModelClass = $listConfig->modelClass ?? null;
                        if (!$allowedModelClass || ltrim($modelClass, '\\') !== ltrim($allowedModelClass, '\\')) {
                            throw new \InvalidArgumentException('Model class not permitted for this controller');
                        }

                        // D-01: Validate field is a listtoggle column
                        $columnConfig = $controller->listGetColumns();
                        $isListToggle = false;
                        foreach ($columnConfig as $colName => $colDef) {
                            if ($colName === $field && ($colDef->type === 'listtoggle' || (isset($colDef->config['type']) && $colDef->config['type'] === 'listtoggle'))) {
                                $isListToggle = true;
                                break;
                            }
                        }
                        if (!$isListToggle) {
                            throw new \InvalidArgumentException('Field is not a listtoggle column in this controller');
                        }

                        // All validation passed -- toggle the field
                        $item = $allowedModelClass::findOrFail($id);
                        $item->{$field} = !$item->{$field};
                        $item->save();

                        return $controller->listRefresh($controller->primaryDefinition);
                    }
                );
            }
        );

        // Register blog URL validation middleware
        $this->registerBlogValidationMiddleware();

        // Register API middleware aliases
        $this->app['router']->aliasMiddleware(
            'token.auth',
            \Golem15\Apparatus\Middleware\TokenAuthenticate::class
        );
        $this->app['router']->aliasMiddleware(
            'json.response',
            \Golem15\Apparatus\Middleware\ForceJsonResponse::class
        );

        // Extend Backend User model with API tokens relation
        \Backend\Models\User::extend(function ($model) {
            $model->hasMany['api_tokens'] = [
                PersonalApiToken::class,
                'key' => 'backend_user_id',
            ];
        });

        // Extend My Account page with API Tokens tab
        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof \Backend\Controllers\Users) {
                return;
            }
            if ($widget->getContext() !== 'myaccount') {
                return;
            }
            if (!$widget->model instanceof \Backend\Models\User) {
                return;
            }

            $widget->addTabFields([
                'api_tokens' => [
                    'tab'  => 'API Tokens',
                    'type' => 'partial',
                    'path' => '~/plugins/golem15/apparatus/partials/_api_tokens.php',
                ],
            ]);
        });

        // Add AJAX handlers for API token management on My Account page
        \Backend\Controllers\Users::extend(function ($controller) {
            $controller->addDynamicMethod('onCreateApiToken', function () use ($controller) {
                $user = \Backend\Facades\BackendAuth::getUser();
                if (!$user) {
                    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                        'Authentication required.'
                    );
                }
                $name = trim(post('token_name', ''));

                if (empty($name)) {
                    throw new \ValidationException(['token_name' => ['Token name is required.']]);
                }

                $plainToken = PersonalApiToken::generateToken();

                $token = new PersonalApiToken();
                $token->backend_user_id = $user->id;
                $token->name = $name;
                $token->token = PersonalApiToken::hashToken($plainToken);

                $expiresAt = post('token_expires_at');
                if (!empty($expiresAt)) {
                    $token->expires_at = $expiresAt;
                }

                $token->save();

                Flash::success('API token created successfully.');

                return ['#apiTokensContainer' => $controller->makePartial(
                    plugins_path('golem15/apparatus/partials/api_tokens'),
                    ['newToken' => $plainToken]
                )];
            });

            $controller->addDynamicMethod('onRevokeApiToken', function () use ($controller) {
                $user = \Backend\Facades\BackendAuth::getUser();
                if (!$user) {
                    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                        'Authentication required.'
                    );
                }
                $tokenId = post('token_id');

                $token = PersonalApiToken::where('id', $tokenId)
                    ->where('backend_user_id', $user->id)
                    ->firstOrFail();

                $token->delete();

                Flash::success('API token revoked.');

                return ['#apiTokensContainer' => $controller->makePartial(
                    plugins_path('golem15/apparatus/partials/api_tokens')
                )];
            });
        });
    }

    /**
     * Register blog URL validation middleware
     */
    protected function registerBlogValidationMiddleware(): void
    {
        // Load blog configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/blog.php',
            'golem15.apparatus::blog'
        );

        // Register middleware globally for web routes
        if (\Config::get('golem15.apparatus::blog.url_validation.enabled', true)) {
            $this->app['router']->pushMiddlewareToGroup(
                'web',
                \Golem15\Apparatus\Middleware\BlogUrlValidationMiddleware::class
            );
        }
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'ucfirst' => 'ucfirst',
                'human_date' => [$this, 'humanDateFilter'],
                'raw_safe' => [$this, 'rawSafeFilter'],
            ]
        ];
    }

    public function humanDateFilter($dateString) {
        return (new HumanDateExtension())->humanDateFilter($dateString);
    }

    /**
     * Sanitize HTML through the D-12 allowlist (HTMLPurifier).
     *
     * Returns safe HTML preserving allowed tags (p, br, strong, em, a[href],
     * h1-h6, ul, ol, li, blockquote, img[src|alt], iframe[src]) while stripping
     * script, on* handlers, javascript: URLs, and non-allowlisted iframe hosts.
     *
     * @param string|null $html Raw HTML input
     * @return string Sanitized HTML
     */
    public function rawSafeFilter(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return \Golem15\Apparatus\Classes\HtmlSanitizer::clean($html);
    }
}
