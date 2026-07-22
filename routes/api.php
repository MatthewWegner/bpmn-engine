<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowInstanceController;

Route::prefix('api/bpmn')->middleware('api')->group(function () {
    Route::post('/workflows/{definition}/versions', [WorkflowController::class, 'storeVersion']);

    // Real-time instance tracking API
    Route::get('/instances/{id}/tokens', [WorkflowInstanceController::class, 'tokens'])
        ->name('bpmn.api.instances.tokens');
});