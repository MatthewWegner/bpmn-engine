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
    }

    public function register()
    {
        // Bindings will go here later
    }
}