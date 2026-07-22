<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;

class StartEventHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        // Find the outgoing edge and advance to the next sequential node
        $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);
        
        return [$nextNodeId, $userData];
    }
}