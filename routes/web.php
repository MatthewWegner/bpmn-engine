<?php

use Illuminate\Support\Facades\Route;
use Saccharine\BpmnEngine\Http\Controllers\WorkflowController;
use Saccharine\BpmnEngine\Http\Controllers\WorkflowInstanceController;

// The main entry point to view and manage workflows
Route::get('/definitions', [WorkflowController::class, 'index'])->name('bpmn.index');

// Process form submission to create new workflows
Route::post('/definitions', [WorkflowController::class, 'store'])->name('bpmn.store');

// The design editor canvas
Route::get('/definitions/{definition}/design', [WorkflowController::class, 'design'])->name('bpmn.design');

// Instance Tracking & Control Routes
Route::get('/instances', [WorkflowInstanceController::class, 'index'])->name('bpmn.instances.index');
Route::post('/instances/{id}/suspend', [WorkflowInstanceController::class, 'suspend'])->name('bpmn.instances.suspend');
Route::post('/instances/{id}/resume', [WorkflowInstanceController::class, 'resume'])->name('bpmn.instances.resume');
Route::post('/instances/{id}/halt', [WorkflowInstanceController::class, 'halt'])->name('bpmn.instances.halt');