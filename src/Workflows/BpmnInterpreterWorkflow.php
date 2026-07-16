<?php

namespace MatthewWegner\BpmnEngine\Workflows;

use Workflow\Workflow;
use Workflow\ActivityStub;
use Workflow\SignalMethod;
use function Workflow\await;
use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use MatthewWegner\BpmnEngine\Models\WorkflowEdge;
use MatthewWegner\BpmnEngine\Services\GatewayRouter;
use RuntimeException;

class BpmnInterpreterWorkflow extends Workflow
{
    // Define a generic signal receiver that pipes data into the durable Inbox
    #[SignalMethod]
    public function submitUserTask(array $payload)
    {
        $this->inbox->receive($payload);
    }

    public function execute(int $versionId, array $userData)
    {
        // Note: Querying the DB inside a workflow is safe ONLY IF the data is immutable.
        // Since WorkflowVersions and their nodes never change once published, this is fully deterministic.
        $version = WorkflowVersion::with(['nodes', 'edges'])->findOrFail($versionId);
        
        // Find the start event
        $startNode = $version->nodes->where('type', 'startEvent')->first();
        if (!$startNode) {
            throw new RuntimeException("No startEvent found.");
        }

        // Kick off the initial path
        // yield from is required because executePath contains its own yields
        return yield from $this->executePath($version, $startNode->bpmn_element_id, $userData);
    }

    /**
     * Executes a linear sequence of nodes until it hits a terminal state or a join.
     */
    protected function executePath(WorkflowVersion $version, string $startNodeId, array $userData)
    {
        $currentNodeId = $startNodeId;

        while ($currentNodeId !== null) {
            $node = $version->nodes->where('bpmn_element_id', $currentNodeId)->first();

            // Terminal Node (End Event)
            if ($node->type === 'endEvent') {
                return $userData;
            }

            // Service Tasks (Business Logic)
            if ($node->type === 'serviceTask') {
                $activityClass = config("bpmn-engine.activities.{$node->implementation}");
                
                // Yield hands control back to Laravel Workflow to execute this safely on the queues
                $activityResult = yield ActivityStub::make($activityClass, $userData);
                
                // Merge the results back into the global state
                $userData = array_merge($userData, $activityResult);

                // Advance to the next sequential node
                $currentNodeId = $this->getNextSequentialNode($version, $currentNodeId);
            }

            // User Tasks (Human in the loop)
            elseif ($node->type === 'userTask') {
                // Hibernate the workflow until the inbox receives an unread message
                yield await(fn () => $this->inbox->hasUnread());

                // Pop the message out of the inbox securely
                $signalPayload = $this->inbox->nextUnread();
                
                // Once resumed, merge the host app's form/button response back into global state
                if (is_array($signalPayload)) {
                    $userData = array_merge($userData, $signalPayload);
                }
                
                // Advance to the next sequential node in the graph layout
                $currentNodeId = $this->getNextSequentialNode($version, $currentNodeId);
            }

            // Exclusive Gateways (Routing)
            elseif ($node->type === 'exclusiveGateway') {
                $router = new GatewayRouter();
                $currentNodeId = $router->getNextNodeId($version, $currentNodeId, $userData);
            }

            // Parallel Gateways (The AND Split/Join mechanism)
            elseif ($node->type === 'parallelGateway') {
                
                $outgoingEdges = $version->edges->where('source_node_id', $currentNodeId);

                // CASE A: It is a SPLIT (Multiple outgoing paths)
                if ($outgoingEdges->count() > 1) {
                    $parallelTasks = [];

                    // Package every distinct path branch into a concurrent asynchronous promise
                    foreach ($outgoingEdges as $edge) {
                        $targetId = $edge->target_node_id;
                        $parallelTasks[] = \Workflow\Workflow::async(fn() => 
                            yield from $this->executePath($version, $targetId, $userData)
                        );
                    }

                    // CRITICAL: The yield Workflow::all acts as the sync barrier.
                    // This pauses the main workflow until every spawned queue job reports back.
                    $branchResults = yield Workflow::all($parallelTasks);

                    // Merge variable mutations from all concurrent paths back into the main payload
                    foreach ($branchResults as $result) {
                        $userData = array_merge($userData, $result);
                    }

                    // Once merged, find the post-join node to advance the main process pointer.
                    $currentNodeId = $this->findPostJoinNode($version, $currentNodeId);
                    continue;
                }

                // CASE B: It is a JOIN (Single outgoing path, reached by a split branch)
                // If a sub-path execution hits a join gateway, its local sub-routine loop terminates 
                // and returns its payload back up to the Workflow::all barrier above.
                return $userData;
            }

            // Passthrough (StartEvents)
            else {
                $currentNodeId = $this->getNextSequentialNode($version, $currentNodeId);
            }
        }

        return $userData;
    }

    /**
     * Helper to find the immediate next node in a straight line (non-gateway paths)
     */
    protected function getNextSequentialNode(WorkflowVersion $version, string $currentNodeId): ?string
    {
        $edge = $version->edges->where('source_node_id', $currentNodeId)->first();
        return $edge ? $edge->target_node_id : null;
    }

    /**
     * Finds the node immediately following the converging Parallel Join Gateway.
     */
    protected function findPostJoinNode(WorkflowVersion $version, string $splitGatewayId): ?string
    {
        // For standard BPMN layouts, we trace an arbitrary branch from the split down to the join.
        // Once we find the join gateway, we grab its single outgoing edge.
        $firstBranchEdge = $version->edges->where('source_node_id', $splitGatewayId)->first();
        $pointerId = $firstBranchEdge->target_node_id;

        while ($pointerId !== null) {
            $node = $version->nodes->where('bpmn_element_id', $pointerId)->first();
            
            if ($node->type === 'parallelGateway') {
                // We found the converging Join! Get the node immediately after it.
                return $this->getNextSequentialNode($version, $pointerId);
            }
            
            $pointerId = $this->getNextSequentialNode($version, $pointerId);
        }

        return null;
    }
}