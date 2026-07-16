<?php

namespace MatthewWegner\BpmnEngine\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatthewWegner\BpmnEngine\BpmnEngineServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // This ensures Laravel's core schema migrations (like users) are run if needed
        $this->loadLaravelMigrations();
    }

    /**
     * Boot the package's service provider.
     */
    protected function getPackageProviders($app)
    {
        return [
            BpmnEngineServiceProvider::class,
        ];
    }

    /**
     * Tell Testbench to run the package's internal database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}