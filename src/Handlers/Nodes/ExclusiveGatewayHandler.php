<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Services\GatewayRouter;

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

        return [$nextNodeId, $userData];
    }
}
