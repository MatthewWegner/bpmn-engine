<?php

namespace MatthewWegner\BpmnEngine\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'bpmn:install';
    protected $description = 'Install the BPMN Engine, publish assets, and prepare the database';

    public function handle()
    {
        $this->info('Installing BPMN Engine...');

        // Publish the configuration file
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'bpmn-engine-config',
            '--force' => true,
        ]);

        // Publish the frontend assets (bpmn-js canvas)
        $this->info('Publishing frontend assets...');
        $this->call('vendor:publish', [
            '--tag' => 'bpmn-engine-assets',
            '--force' => true,
        ]);

        // Publish Durable Workflow Migrations
        $this->info('Publishing Durable Workflow migrations...');
        $this->call('vendor:publish', [
            '--provider' => 'Workflow\Providers\WorkflowServiceProvider',
            '--tag' => 'migrations',
        ]);

        // Offer to run migrations
        if ($this->confirm('Would you like to run the database migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('BPMN Engine installed successfully!');
        $this->line('You can now navigate to /bpmn/workflows to start designing.');
    }
}