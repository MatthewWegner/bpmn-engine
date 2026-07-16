<?php

namespace MatthewWegner\BpmnEngine\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatthewWegner\BpmnEngine\BpmnEngineServiceProvider;
use Workflow\Providers\WorkflowServiceProvider;

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
     * Define environment setup.
     * This is where we mock the .env variables for Testbench.
     */
    protected function getEnvironmentSetUp($app)
    {
        // Provide a standard 32-character base64 string for Laravel's encrypter
        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYvnkFmTNFjq+95U=');

        // Ensure this uses the database queue
        $app['config']->set('queue.default', 'database');
    }

    /**
     * Boot the package's service provider.
     */
    protected function getPackageProviders($app)
    {
        return [
            WorkflowServiceProvider::class,
            BpmnEngineServiceProvider::class,
        ];
    }

    /**
     * Tell Testbench to run the package's internal database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        // Load the core Durable Workflow migrations first (creates 'workflows', 'workflow_logs', etc.)
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/durable-workflow/workflow/src/migrations');
        
        // Then load our package's migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}