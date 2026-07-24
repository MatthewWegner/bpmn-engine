<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowInstanceController;

// The main entry point to view and manage workflows
Route::get('/bpmn/workflows', [WorkflowController::class, 'index'])->name('bpmn.index');

// Process form submission to create new workflows
Route::post('/bpmn/workflows', [WorkflowController::class, 'store'])->name('bpmn.store');

// The design editor canvas
Route::get('/bpmn/workflows/{definition}/design', [WorkflowController::class, 'design'])->name('bpmn.design');

// Instance Tracking & Control Routes
Route::get('/bpmn/instances', [WorkflowInstanceController::class, 'index'])->name('bpmn.instances.index');
Route::post('/bpmn/instances/{id}/suspend', [WorkflowInstanceController::class, 'suspend'])->name('bpmn.instances.suspend');
Route::post('/bpmn/instances/{id}/resume', [WorkflowInstanceController::class, 'resume'])->name('bpmn.instances.resume');
Route::post('/bpmn/instances/{id}/halt', [WorkflowInstanceController::class, 'halt'])->name('bpmn.instances.halt');