<?php

namespace MatthewWegner\BpmnEngine;

use Illuminate\Support\ServiceProvider;

class BpmnEngineServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load the database migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load the package's internal API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Tell the host app where to find web routes and Blade templates
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bpmn-engine');

        // Allow the host app to publish the config file
        if ($this->app->runningInConsole()) {

            // Register custom artisan commands
            $this->commands([
                \MatthewWegner\BpmnEngine\Console\Commands\MakeActivityCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/bpmn-engine.php' => config_path('bpmn-engine.php'),
            ], 'bpmn-engine-config');
            
            // Allow host applications to customize/override the view layout if needed
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/bpmn-engine'),
            ], 'bpmn-engine-views');
            
            // Publish compiled frontend assets
            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/bpmn-engine'),
            ], 'bpmn-engine-assets');
        }
    }

    public function register()
    {
        // Merge the host app's published config with our package defaults
        $this->mergeConfigFrom(
            __DIR__ . '/../config/bpmn-engine.php', 'bpmn-engine'
        );
    }
}