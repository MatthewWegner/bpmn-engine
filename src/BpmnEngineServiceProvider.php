<?php

namespace MatthewWegner\BpmnEngine;

use Illuminate\Support\ServiceProvider;

class BpmnEngineServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        // Bindings will go here later
    }
}