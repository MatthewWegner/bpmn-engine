<?php

namespace MatthewWegner\BpmnEngine\Handlers\Nodes;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Workflows\BpmnInterpreterWorkflow;
use MatthewWegner\BpmnEngine\Models\WorkflowNode;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use function Workflow\await;

class UserTaskHandler implements BpmnNodeHandlerInterface
{
    public function handle(
        BpmnInterpreterWorkflow $workflow,
        WorkflowNode $node,
        WorkflowVersion $version,
        array $userData,
        ?int $instanceId
    ): \Generator
    {
        // Announce to the host application that a human is needed
        event(new \MatthewWegner\BpmnEngine\Events\UserTaskPending(
            $workflow->uniqueId(), 
            $node->name,
            $userData
        ));

        // Hibernate the workflow until the inbox receives an unread message
        // We also need to allow halting/suspending while waiting for a user!
        yield await(fn () => $workflow->inbox->hasUnread() || $workflow->isSuspended() || $workflow->isHalted());

        // If woken by a manual intervention, bypass reading the inbox and return immediately
        if ($workflow->isSuspended() || $workflow->isHalted()) {
            return [$node->bpmn_element_id, $userData]; // Return same node ID to re-evaluate at top of loop
        }

        // Pop the message out of the inbox securely
        $signalPayload = $workflow->inbox->nextUnread();
        
        // Once resumed, merge the host app's form/button response back into global state
        if (is_array($signalPayload)) {
            $userData = array_merge($userData, $signalPayload);
        }
        
        // Find the outgoing edge and advance to the next sequential node
        $nextNodeId = $workflow->getNextSequentialNode($version, $node->bpmn_element_id);

        // Advance to the next sequential node in the graph layout
        return [$nextNodeId, $userData];
    }
}
