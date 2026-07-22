<?php

namespace MatthewWegner\BpmnEngine\Contracts;

use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;

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