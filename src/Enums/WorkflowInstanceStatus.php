<?php

namespace MatthewWegner\BpmnEngine\Enums;

enum WorkflowInstanceStatus: string
{
    case RUNNING = 'running';
    case SUSPENDED = 'suspended';
    case HALTED = 'halted';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}