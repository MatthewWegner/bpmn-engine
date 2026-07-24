<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowInstanceController;

// The prefix and middleware are handled by the Service Provider
Route::post('/workflows/{definition}/versions', [WorkflowController::class, 'storeVersion']);

// Real-time instance tracking API
Route::get('/instances/{id}/tokens', [WorkflowInstanceController::class, 'tokens'])
    ->name('bpmn.api.instances.tokens');