<?php

namespace MatthewWegner\BpmnEngine;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Listeners\WorkflowTriggerListener;

class BpmnEngineServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load the database migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Tell the host app where to find Blade templates
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bpmn-engine');

        // Load the package's internal API routes
        Route::prefix('api/bpmn')
            ->middleware(config('bpmn-engine.api_middleware', ['api']))
            ->group(__DIR__ . '/../routes/api.php');

        // Tell the host app where to find web routes
        Route::middleware(config('bpmn-engine.middleware', ['web']))
            ->group(__DIR__ . '/../routes/web.php');

        // Define default gates for capability control
        Gate::define('bpmn:view', function ($user = null) {
            // Default fallback: host app can override this in their AuthServiceProvider
            return app()->environment('local'); 
        });

        Gate::define('bpmn:edit', function ($user = null) {
            return app()->environment('local');
        });

        // Listen to all events to check for start event triggers
        Event::listen('*', [WorkflowTriggerListener::class, 'handle']);

        if ($this->app->runningInConsole()) {
            // Register custom artisan commands
            $this->commands([
                \MatthewWegner\BpmnEngine\Console\Commands\MakeActivityCommand::class,
                \MatthewWegner\BpmnEngine\Console\Commands\MakeTriggerCommand::class,
                \MatthewWegner\BpmnEngine\Console\Commands\MakeTemplateCommand::class,
                \MatthewWegner\BpmnEngine\Console\Commands\InstallCommand::class,
                \MatthewWegner\BpmnEngine\Console\Commands\DemoCommand::class,
                \MatthewWegner\BpmnEngine\Console\Commands\InstanceControlCommand::class,
            ]);

            // Allow the host app to publish the config file
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

        $this->registerGates();
    }

    public function register()
    {
        // Merge the host app's published config with our package defaults
        $this->mergeConfigFrom(
            __DIR__ . '/../config/bpmn-engine.php', 'bpmn-engine'
        );
    }

    /**
     * Register the default authorization gates for the BPMN Engine.
     */
    protected function registerGates()
    {
        // View the dashboards and editor
        Gate::define('bpmn:view', fn ($user = null) => app()->environment('local'));
        
        // Create workflows and save diagram changes
        Gate::define('bpmn:edit', fn ($user = null) => app()->environment('local'));
        
        // Control running workflow instances
        Gate::define('bpmn:suspend-instance', fn ($user = null) => app()->environment('local'));
        Gate::define('bpmn:resume-instance', fn ($user = null) => app()->environment('local'));
        Gate::define('bpmn:halt-instance', fn ($user = null) => app()->environment('local'));
    }
}