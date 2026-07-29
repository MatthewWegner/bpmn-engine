<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    // Clean up the generated templates directory after each test
    File::deleteDirectory(resource_path('bpmn'));
});

it('generates a new BPMN element template using command options', function () {
    $this->artisan('bpmn:make-template', [
        'name' => 'Send Welcome Email',
        '--key' => 'send_email'
    ])
    ->expectsOutputToContain('BPMN Element Template generated successfully')
    ->assertSuccessful();

    $templatePath = resource_path('bpmn/templates/send-welcome-email.json');
    expect(File::exists($templatePath))->toBeTrue();

    $content = File::get($templatePath);
    
    expect($content)->toContain('"name": "Send Welcome Email"');
    // Notice the updated expectation below!
    expect($content)->toContain('"id": "com.laravel.tasks.SendWelcomeEmail"');
    expect($content)->toContain('"value": "send_email"');
});

it('prompts for the activity key if it is not provided', function () {
    $this->artisan('bpmn:make-template', ['name' => 'Update Record'])
    ->expectsQuestion('What is the Activity implementation key? (e.g., send_welcome_email)', 'update_record')
    ->assertSuccessful();

    $templatePath = resource_path('bpmn/templates/update-record.json');
    expect(File::exists($templatePath))->toBeTrue();
    
    $content = File::get($templatePath);
    expect($content)->toContain('"value": "update_record"');
});

it('aborts if the template file already exists', function () {
    // Create the directory and file beforehand
    File::makeDirectory(resource_path('bpmn/templates'), 0755, true);
    File::put(resource_path('bpmn/templates/existing-task.json'), '{}');

    $this->artisan('bpmn:make-template', [
        'name' => 'Existing Task',
        '--key' => 'existing_key'
    ])
    ->expectsOutputToContain('Template existing-task.json already exists!')
    ->assertSuccessful(); // The command exits cleanly but warns the user
});