<?php

namespace MatthewWegner\BpmnEngine\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Workflow\Providers\WorkflowServiceProvider;
use MatthewWegner\BpmnEngine\BpmnEngineServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // This ensures Laravel's core schema migrations (like users) are run if needed
        $this->loadLaravelMigrations();

        // Automatically grant BPMN gates during test runs so they don't throw 403s
        // The '?' is critical here! It tells Laravel to run this even for guests.
        Gate::before(function (?Authenticatable $user, $ability) {
            if (str_starts_with($ability, 'bpmn:')) {
                return true; // Auto-pass all BPMN permissions during testing
            }
        });
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

        // Force all database connections to use memory
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Disable the failed_jobs log provider completely for testing context
        $app['config']->set('queue.failed', [
            'driver' => 'null',
        ]);
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