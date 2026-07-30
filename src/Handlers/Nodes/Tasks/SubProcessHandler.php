<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes\Tasks;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Handlers\Traits\EvaluatesBoundaryEvents;
use Throwable;

class SubProcessHandler implements BpmnNodeHandlerInterface
{
    use EvaluatesBoundaryEvents;
    
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator {
        
        try {
            // Yield the scoped child workflow, passing the subProcess node ID
            $childWorkflowResult = yield $workflow->makeScopedChildWorkflow(
                $version->id,
                $node->bpmn_element_id,
                $userData,
                $instanceId
            );

            // Merge the returned data back into the parent payload
            $userData = array_merge($userData, $childWorkflowResult);

            // Determine the next node to advance to in the parent process
            $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);

        } catch (Throwable $e) {
            // Failure: Check if we have an escape route defined in the BPMN diagram
            // Delegate to the boundary event trait
            $nextNodeId = $this->handleErrorBoundary($e, $version, $node->bpmn_element_id, $workflow, $userData);
        }

        return [$nextNodeId, $userData];
    }
}