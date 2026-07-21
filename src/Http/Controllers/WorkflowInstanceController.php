<?php

namespace MatthewWegner\BpmnEngine\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use MatthewWegner\BpmnEngine\Models\WorkflowInstance;
use Workflow\WorkflowStub;

class WorkflowInstanceController extends Controller
{
    /**
     * Display a list of all active and historical workflow instances.
     */
    public function index()
    {
        $instances = WorkflowInstance::with('version.definition')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // We will build a 'bpmn-engine::instances.index' view to display this table
        return view('bpmn-engine::instances.index', compact('instances'));
    }

    /**
     * Suspend a running workflow.
     */
    public function suspend($id)
    {
        $instance = WorkflowInstance::findOrFail($id);

        if ($instance->status !== 'running') {
            return back()->with('error', 'Only running workflows can be suspended.');
        }

        // Update the relational state. The engine loop will read this on its next cycle.
        $instance->update(['status' => 'suspended']);

        return back()->with('success', "Workflow instance [{$id}] suspended.");
    }

    /**
     * Resume a suspended workflow.
     */
    public function resume($id)
    {
        $instance = WorkflowInstance::findOrFail($id);

        if ($instance->status !== 'suspended') {
            return back()->with('error', 'Only suspended workflows can be resumed.');
        }

        // 1. Update the database state
        $instance->update(['status' => 'running']);

        // 2. Load the durable coroutine and fire a signal to wake it up
        $workflow = WorkflowStub::load($instance->durable_workflow_id);
        $workflow->signal('workflow_resumed', ['action' => 'resume']);

        return back()->with('success', "Workflow instance [{$id}] resumed.");
    }

    /**
     * Permanently halt/cancel a running or suspended workflow.
     */
    public function halt($id)
    {
        $instance = WorkflowInstance::findOrFail($id);

        if (in_array($instance->status, ['completed', 'failed', 'halted'])) {
            return back()->with('error', 'This workflow is already in a terminal state.');
        }

        $instance->update(['status' => 'halted']);

        // Signal the engine to break its execution loop and clean up
        $workflow = WorkflowStub::load($instance->durable_workflow_id);
        $workflow->signal('workflow_halted', ['action' => 'halt']);

        return back()->with('success', "Workflow instance [{$id}] permanently halted.");
    }
}