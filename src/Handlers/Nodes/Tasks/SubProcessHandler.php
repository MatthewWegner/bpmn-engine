<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes\Tasks;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;

class SubProcessHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator {
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

        return [$nextNodeId, $userData];
    }
}