<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;

class EndEventHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        // Terminal node; there is no next node.
        return [null, $userData];
    }
}