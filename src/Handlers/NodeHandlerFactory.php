<?php

namespace MatthewWegner\BpmnEngine\Handlers;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Handlers\Nodes;

class NodeHandlerFactory
{
    protected static array $handlers = [
        'startEvent'       => Nodes\Events\StartEventHandler::class,
        'endEvent'         => Nodes\Events\EndEventHandler::class,
        'serviceTask'      => Nodes\Tasks\ServiceTaskHandler::class,
        'userTask'         => Nodes\Tasks\UserTaskHandler::class,
        'exclusiveGateway' => Nodes\Gateways\ExclusiveGatewayHandler::class,
        'parallelGateway'  => Nodes\Gateways\ParallelGatewayHandler::class,
    ];

    public static function make(string $type): BpmnNodeHandlerInterface
    {
        $handlerClass = self::$handlers[$type] ?? null;

        if (!$handlerClass) {
            throw new \RuntimeException("BPMN Engine Error: No handler registered for node type [{$type}].");
        }

        return new $handlerClass();
    }
}