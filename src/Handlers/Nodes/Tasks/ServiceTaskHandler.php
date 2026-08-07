<?php

namespace Saccharine\BpmnEngine\Handlers\Nodes\Tasks;

use Saccharine\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use Saccharine\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use Saccharine\BpmnEngine\Models\WorkflowNode;
use Saccharine\BpmnEngine\Models\WorkflowVersion;
use Saccharine\BpmnEngine\Handlers\Traits\EvaluatesBoundaryEvents;
use Throwable;

// Service Tasks (Business Logic)
class ServiceTaskHandler implements BpmnNodeHandlerInterface
{
    use EvaluatesBoundaryEvents;

    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        $activityClass = config("bpmn-engine.activities.{$node->implementation}");

        try {
            // Attempt the standard execution
            
            // Yield hands control back to Laravel Workflow to execute this safely on the queues
            // Use the workflow's wrapper
            $activityResult = yield $workflow->makeActivity($activityClass, $userData);

            // Merge the results back into the global state
            $userData = array_merge($userData, $activityResult);

            // Success: Proceed down the normal sequence flow
            $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);

        } catch (Throwable $e) {
            // Failure: Check if we have an escape route defined in the BPMN diagram
            // Delegate to the boundary event trait
            $nextNodeId = $this->handleErrorBoundary($e, $version, $node->bpmn_element_id, $workflow, $userData);
        }
        
        return [$nextNodeId, $userData];
    }
}
