<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes\Tasks;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Handlers\Traits\EvaluatesBoundaryEvents;
use Workflow\ActivityStub;
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
