<?php

namespace MatthewWegner\BpmnEngine\Workflows;

use Workflow\Workflow;
use Workflow\WorkflowStub;
use Workflow\ActivityStub;
use Workflow\ChildWorkflowStub;
use Workflow\SignalMethod;
use function Workflow\await;
use function Workflow\all;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Models\WorkflowEdge;
use MatthewWegner\BpmnEngine\Models\WorkflowToken;
use MatthewWegner\BpmnEngine\Services\GatewayRouter;
use MatthewWegner\BpmnEngine\Handlers\NodeHandlerFactory;
use RuntimeException;

class BpmnInterpreterWorkflow extends Workflow
{
    // Internal state trackers for manual interventions
    private bool $isSuspended = false;
    private bool $isHalted = false;

    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function isHalted(): bool
    {
        return $this->isHalted;
    }

    // Define a generic signal receiver that pipes data into the durable Inbox
    #[SignalMethod]
    public function submitUserTask(array $payload)
    {
        $this->inbox->receive($payload);
    }

    // Workflow intervention signals
    #[SignalMethod]
    public function suspendWorkflow()
    {
        $this->isSuspended = true;
    }

    #[SignalMethod]
    public function resumeWorkflow()
    {
        $this->isSuspended = false;
    }

    #[SignalMethod]
    public function haltWorkflow()
    {
        $this->isHalted = true;
    }

    // Add the optional 3rd parameter for branch executions
    public function execute(
        int $versionId,
        array $userData,
        ?string $startNodeId = null,
        ?int $instanceId = null
    ) {
        // Note: Querying the DB inside a workflow is safe ONLY IF the data is immutable.
        // Since WorkflowVersions and their nodes never change once published,
        // this is fully deterministic.
        $version = WorkflowVersion::with(['nodes', 'edges'])->findOrFail($versionId);

        // If no start node is provided, this is the Master Workflow starting from the beginning
        if ($startNodeId === null) {
            // Find the start event
            $startNode = $version->nodes->where('type', 'startEvent')->first();
            if (!$startNode) {
                throw new RuntimeException("No startEvent found.");
            }
            $currentNodeId = $startNode->bpmn_element_id;
        } else {
            // This is a Child Workflow starting its specific branch
            $currentNodeId = $startNodeId;
        }

        while ($currentNodeId !== null) {
            // HALT CHECK: Immediately break the loop and terminate the process
            if ($this->isHalted) {
                // You can optionally inject a 'halted_at' flag into the payload
                $userData['_system_status'] = 'halted';
                break; 
            }

            // SUSPENSION CHECK: Hibernate safely until resumed (or halted while sleeping)
            if ($this->isSuspended) {
                yield await(fn () => !$this->isSuspended || $this->isHalted);
                
                // Continue forces the loop to re-evaluate the Halt check immediately upon waking up
                continue; 
            }

            // Token tracker: SideEffect ensures this database query only runs exactly once per step, never on replay
            if ($instanceId !== null) {
                yield WorkflowStub::sideEffect(function () use ($instanceId, $currentNodeId) {
                    WorkflowToken::updateOrCreate(
                        [
                            'workflow_instance_id' => $instanceId,
                            'durable_workflow_id'  => $this->uniqueId(),
                        ],
                        [
                            'bpmn_element_id' => $currentNodeId,
                        ]
                    );
                });
            }

            $node = $version->nodes->where('bpmn_element_id', $currentNodeId)->first();

            // RESOLVE HANDLER
            $handler = NodeHandlerFactory::make($node->type);

            // DELEGATE EXECUTION
            // We use 'yield from' because the handler itself contains yields (Activities/Awaits)
            [$nextNodeId, $userData] = yield from $handler->handle(
                $this, 
                $node, 
                $version, 
                $userData, 
                $instanceId
            );

            // Terminal Condition (End of Process)
            if ($node->type === 'endEvent') {
                $this->cleanupToken($instanceId);
                return $userData;
            }

            $currentNodeId = $nextNodeId;
        }

        // Failsafe cleanup if the loop breaks
        $this->cleanupToken($instanceId);
        
        return $userData;
    }

    /**
     * Helper to find the immediate next node in a straight line (non-gateway paths)
     */
    public function getNextSequentialNode(WorkflowVersion $version, string $currentNodeId): ?string
    {
        $edge = $version->edges->where('source_node_id', $currentNodeId)->first();
        return $edge ? $edge->target_node_id : null;
    }
    
    /**
     * Removes the active token from the database when a thread terminates.
     */
    protected function cleanupToken(?int $instanceId): void
    {
        if ($instanceId !== null) {
            // Because side effects cannot be yielded from a void return easily inside the execution structure,
            // we can trigger this directly in a blocking manner since it's the final action of the thread.
            WorkflowToken::where('durable_workflow_id', $this->uniqueId())->delete();
        }
    }
}