<?php

namespace Saccharine\BpmnEngine\Contracts;

use Saccharine\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use Saccharine\BpmnEngine\Models\WorkflowNode;
use Saccharine\BpmnEngine\Models\WorkflowVersion;

interface BpmnNodeHandlerInterface
{
    /**
     * Handle the execution of a specific BPMN node.
     *
     * @return \Generator Yields an array containing the next node ID and the mutated user data: [?string, array]
     */
    public function handle(
        BpmnInterpreterWorkflow $workflow, 
        WorkflowNode $node, 
        WorkflowVersion $version, 
        array $userData, 
        ?int $instanceId
    ): \Generator;
}