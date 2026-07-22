<?php

namespace MatthewWegner\BpmnEngine\Handlers;

use MatthewWegner\BpmnEngine\Contracts\BpmnNodeHandlerInterface;
use MatthewWegner\BpmnEngine\Handlers\Nodes\StartEventHandler;
use MatthewWegner\BpmnEngine\Handlers\Nodes\EndEventHandler;
use MatthewWegner\BpmnEngine\Handlers\Nodes\ServiceTaskHandler;
use MatthewWegner\BpmnEngine\Handlers\Nodes\UserTaskHandler;
use MatthewWegner\BpmnEngine\Handlers\Nodes\ExclusiveGatewayHandler;
use MatthewWegner\BpmnEngine\Handlers\Nodes\ParallelGatewayHandler;

class NodeHandlerFactory
{
    protected static array $handlers = [
        'startEvent'       => StartEventHandler::class,
        'endEvent'         => EndEventHandler::class,
        'serviceTask'      => ServiceTaskHandler::class,
        'userTask'         => UserTaskHandler::class,
        'exclusiveGateway' => ExclusiveGatewayHandler::class,
        'parallelGateway'  => ParallelGatewayHandler::class,
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