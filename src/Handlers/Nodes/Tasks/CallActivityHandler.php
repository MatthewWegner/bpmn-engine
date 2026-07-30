<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes\Tasks;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Models\WorkflowDefinition;
use MatthewWegner\BpmnEngine\Handlers\Traits\EvaluatesBoundaryEvents;
use Throwable;
use RuntimeException;

class CallActivityHandler implements BpmnNodeHandlerInterface
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
            $targetDefinitionKey = $node->implementation;
            
            // Look up the target workflow definition and its active version
            $targetDefinition = WorkflowDefinition::where('key', $targetDefinitionKey)->first();
            $targetVersion = $targetDefinition ? $targetDefinition->versions()->where('is_active', true)->first() : null;

            if (!$targetDefinition || !$targetVersion) {
                throw new RuntimeException("BPMN Engine Error: Target workflow [{$targetDefinitionKey}] not found or has no active version.");
            }

            // Yield the ChildWorkflowStub created by the interpreter
            // We start the target workflow from its beginning (startNodeId = null)
            $childWorkflowResult = yield $workflow->makeChildWorkflow(
                $targetVersion->id, 
                $userData, 
                null, 
                $instanceId
            );

            // Merge the returned data back into the parent payload
            $userData = array_merge($userData, $childWorkflowResult);

            // Determine the next node to advance to
            $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);

        } catch (Throwable $e) {
            // Failure: Check if we have an escape route defined in the BPMN diagram
            // Delegate to the boundary event trait
            $nextNodeId = $this->handleErrorBoundary($e, $version, $node->bpmn_element_id, $workflow, $userData);
        }

        return [$nextNodeId, $userData];
    }
}