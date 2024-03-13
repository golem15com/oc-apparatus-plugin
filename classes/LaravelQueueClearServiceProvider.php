<?php namespace Golem15\Apparatus\Classes;

use Illuminate\Support\ServiceProvider;
use Golem15\Apparatus\Contracts\Clearer;

class LaravelQueueClearServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            Clearer::class,
            \Golem15\Apparatus\Classes\Clearer::class
        );
        $this->commands('Golem15\Apparatus\Console\QueueClearCommand');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
