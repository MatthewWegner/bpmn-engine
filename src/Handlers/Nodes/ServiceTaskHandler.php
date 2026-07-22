<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use Workflow\ActivityStub;
use Throwable;

// Service Tasks (Business Logic)
class ServiceTaskHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        $activityClass = config("bpmn-engine.activities.{$node->implementation}");

        // Look for any error boundary events attached to this specific task
        $errorBoundaryNode = $version->nodes
            ->where('type', 'boundaryEvent')
            ->where('event_definition_type', 'error')
            ->where('attached_to_element_id', $node->bpmn_element_id)
            ->first();

        try {
            // Attempt the standard execution
            
            // Yield hands control back to Laravel Workflow to execute this safely on the queues
            $activityResult = yield ActivityStub::make($activityClass, $userData);

            // Merge the results back into the global state
            $userData = array_merge($userData, $activityResult);

            // Success: Proceed down the normal sequence flow
            $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);

        } catch (Throwable $e) {
            // Failure: Check if we have an escape route defined in the BPMN diagram
            if ($errorBoundaryNode) {
                // We caught the anticipated error! 
                // Store the error message in the payload so subsequent tasks can react to it.
                $userData['_error_caught'] = $e->getMessage();
                
                // Route the token out through the boundary event's sequence flow
                $nextNodeId = $workflow->getNextSequentialNode($version, $errorBoundaryNode->bpmn_element_id);
            } else {
                // No boundary event defined. Throw the error back up so durable-workflow 
                // can handle the standard failure, logging, and retry logic.
                throw $e;
            }
        }
        
        return [$nextNodeId, $userData];
    }
}
