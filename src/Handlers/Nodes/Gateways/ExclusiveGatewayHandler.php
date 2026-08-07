<?php

namespace Saccharine\BpmnEngine\Handlers\Nodes\Gateways;

use Saccharine\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use Saccharine\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use Saccharine\BpmnEngine\Models\WorkflowNode;
use Saccharine\BpmnEngine\Models\WorkflowVersion;
use Saccharine\BpmnEngine\Services\GatewayRouter;

// Exclusive Gateways (Routing)
class ExclusiveGatewayHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        $router = new GatewayRouter();
        $nextNodeId = $router->getNextNodeId($version, $node->bpmn_element_id, $userData);

        yield from []; // Satisfies the Generator return type
        return [$nextNodeId, $userData];
    }
}
