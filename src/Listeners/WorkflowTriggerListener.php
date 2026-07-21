<?php

namespace MatthewWegner\BpmnEngine\Listeners;

use Illuminate\Support\Facades\DB;
use MatthewWegner\BpmnEngine\Contracts\BpmnTriggerableEvent;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use Workflow\WorkflowStub;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowInstance;
use MatthewWegner\BpmnEngine\Enums\WorkflowInstanceStatus;

class WorkflowTriggerListener
{
    public function handle(string $eventName, array $payload)
    {
        // REVERSE LOOKUP: Check if the fired event class is registered in our config
        $triggers = config('bpmn-engine.triggers', []);
        $triggerAlias = array_search($eventName, $triggers);

        // If it's not registered as a trigger, the engine ignores it entirely
        if (!$triggerAlias) {
            return;
        }

        // Extract the actual event instance from Laravel's payload array
        $eventInstance = $payload[0] ?? null;

        // Fast exit if this event doesn't implement our contract
        if (!$eventInstance instanceof BpmnTriggerableEvent) {
            return;
        }

        // Find all active workflow versions that have a StartEvent mapped to this exact event alias
        $startNodes = WorkflowNode::where('type', 'startEvent')
            ->where('implementation', $triggerAlias)
            ->whereHas('version', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        if ($startNodes->isEmpty()) {
            return;
        }

        $businessKey = $eventInstance->getBusinessKey();
        $workflowPayload = $eventInstance->getWorkflowPayload();

        // Pluck only unique version IDs to prevent duplicate launches on messy diagrams
        $uniqueVersionIds = $startNodes->pluck('workflow_version_id')->unique();

        foreach ($uniqueVersionIds as $versionId) {
            // Enforce Idempotency using a safe database transaction
            DB::transaction(function () use ($versionId, $businessKey, $workflowPayload) {
                $alreadyRan = DB::table('workflow_triggers_log')
                    ->where('workflow_version_id', $versionId)
                    ->where('business_key', $businessKey)
                    ->exists();

                if ($alreadyRan) {
                    return; // Skip: This process already triggered for this specific domain object
                }
                
                // Launch the Durable Workflow!
                $workflow = WorkflowStub::make(BpmnInterpreterWorkflow::class);

                // Create an Instance using Eloquent so we can capture the new ID
                $instance = WorkflowInstance::create([
                    'workflow_version_id' => $versionId,
                    'status'              => WorkflowInstanceStatus::RUNNING,
                    'durable_workflow_id' => $workflow->id(),
                ]);

                // Parameters: versionId, userData, startNodeId (null for master), instanceId
                $workflow->start($versionId, $workflowPayload, null, $instance->id);

                // Log the trigger to prevent future duplicates
                DB::table('workflow_triggers_log')->insert([
                    'workflow_version_id' => $versionId,
                    'business_key'        => $businessKey,
                    'durable_workflow_id' => $workflow->id(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            });
        }
    }
}