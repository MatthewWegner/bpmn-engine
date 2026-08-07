<?php

namespace Saccharine\BpmnEngine\Handlers\Nodes\Events;

use Saccharine\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use Saccharine\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use Saccharine\BpmnEngine\Models\WorkflowNode;
use Saccharine\BpmnEngine\Models\WorkflowVersion;

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
        yield from []; // Satisfies the Generator return type
        
        // Terminal node; there is no next node.
        return [null, $userData];
    }
}