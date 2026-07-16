<?php

namespace MatthewWegner\BpmnEngine\Workflows;

use Workflow\Workflow;
use Workflow\ActivityStub;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Models\WorkflowEdge;
use MatthewWegner\BpmnEngine\Services\GatewayRouter;
use RuntimeException;

class BpmnInterpreterWorkflow extends Workflow
{
    public function execute(int $versionId, array $userData)
    {
        // Note: Querying the DB inside a workflow is safe ONLY IF the data is immutable.
        // Since WorkflowVersions and their nodes never change once published, this is fully deterministic.
        $version = WorkflowVersion::with(['nodes', 'edges'])->findOrFail($versionId);
        
        // Find the start event
        $startNode = $version->nodes->where('type', 'startEvent')->first();
        if (!$startNode) {
            throw new RuntimeException("No startEvent found for Workflow Version [{$versionId}].");
        }

        $currentNodeId = $startNode->bpmn_element_id;

        // The Engine Loop
        while ($currentNodeId !== null) {
            $node = $version->nodes->where('bpmn_element_id', $currentNodeId)->first();

            // Terminal Condition
            if ($node->type === 'endEvent') {
                break;
            }

            // Execute Business Logic (Service Tasks)
            if ($node->type === 'serviceTask') {
                $activityKey = $node->implementation;
                $activityClass = config("bpmn-engine.activities.{$activityKey}");

                if (!$activityClass || !class_exists($activityClass)) {
                    throw new RuntimeException("Activity mapped to [{$activityKey}] not found in host configuration.");
                }

                // Yield hands control back to Laravel Workflow to execute this safely on the queues
                $activityResult = yield ActivityStub::make($activityClass, $userData);
                
                // Merge the results back into the global state
                $userData = array_merge($userData, $activityResult);

                // Advance to the next sequential node
                $currentNodeId = $this->getNextSequentialNode($version, $currentNodeId);
            }

            // Routing (Exclusive Gateways)
            elseif ($node->type === 'exclusiveGateway') {
                $router = new GatewayRouter();
                $currentNodeId = $router->getNextNodeId($version, $currentNodeId, $userData);
            }

            // Passthrough (Start Events, etc.)
            else {
                $currentNodeId = $this->getNextSequentialNode($version, $currentNodeId);
            }
        }

        return $userData;
    }

    /**
     * Helper to find the immediate next node in a straight line (non-gateway paths)
     */
    protected function getNextSequentialNode(WorkflowVersion $version, string $currentNodeId): ?string
    {
        $edge = $version->edges->where('source_node_id', $currentNodeId)->first();
        return $edge ? $edge->target_node_id : null;
    }
}