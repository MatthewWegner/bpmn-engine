<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;

Route::prefix('api/bpmn')->middleware('api')->group(function () {
    Route::post('/workflows/{definition}/versions', [WorkflowController::class, 'storeVersion']);
});