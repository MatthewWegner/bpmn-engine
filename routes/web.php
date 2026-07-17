<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;

Route::middleware('web')->group(function () {
    // The main entry point to view and manage workflows
    Route::get('/bpmn/workflows', [WorkflowController::class, 'index'])->name('bpmn.index');
    
    // Process form submission to create new workflows
    Route::post('/bpmn/workflows', [WorkflowController::class, 'store'])->name('bpmn.store');
    
    // The design editor canvas
    Route::get('/bpmn/workflows/{definition}/design', [WorkflowController::class, 'design'])->name('bpmn.design');
});