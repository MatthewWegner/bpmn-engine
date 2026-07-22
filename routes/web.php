<?php

use Illuminate\Support\Facades\Route;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowController;
use MatthewWegner\BpmnEngine\Http\Controllers\WorkflowInstanceController;

use MatthewWegner\BpmnEngine\Models\WorkflowDefinition;
use MatthewWegner\BpmnEngine\Models\WorkflowInstance;
use MatthewWegner\BpmnEngine\Enums\WorkflowInstanceStatus;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use Workflow\WorkflowStub;
use Illuminate\Http\Request;

Route::middleware('web')->group(function () {
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
});

// The Demo UI Route
Route::get('/live-demo', function () {
    // Fetch the demo definition you generated earlier
    $definition = WorkflowDefinition::where('key', 'demo-order-processing')->firstOrFail();
    $version = $definition->versions()->where('is_active', true)->firstOrFail();

    // Start a fresh, tracking-enabled instance
    $workflow = WorkflowStub::make(BpmnInterpreterWorkflow::class);
    $instance = WorkflowInstance::create([
        'workflow_version_id' => $version->id,
        'status'              => WorkflowInstanceStatus::RUNNING,
        'durable_workflow_id' => $workflow->id(),
    ]);
    
    // We pass 1500 to guarantee the token routes to the "Manager Review" UserTask
    $workflow->start($version->id, [
        'order_id' => 'ORD-' . rand(1000, 9999), 
        'amount' => 1500,
        'customer' => 'Family Demo User'
    ], null, $instance->id);
    
    return view('bpmn-engine::live-demo', [
        'instance' => $instance, 
        'xml' => $version->bpmn_xml
    ]);
});

// The Demo Approval Action
Route::post('/live-demo/{id}/approve', function ($id, Request $request) {
    $instance = WorkflowInstance::findOrFail($id);
    $workflow = WorkflowStub::load($instance->durable_workflow_id);
    
    // Fire the signal to wake the engine up!
    $workflow->submitUserTask([
        'manager_action' => 'approved',
        'manager_notes'  => 'Approved via Live Demo UI'
    ]);
    
    return response()->json(['success' => true]);
});