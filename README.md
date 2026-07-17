# **Laravel BPMN Engine**

A lightweight, native PHP workflow orchestrator and visual designer for Laravel. This package allows you to model business processes visually using BPMN 2.0 and execute them as durable, stateful background jobs—without any external Java dependencies (like Camunda or Zeebe).  

It bridges the gap between visual business diagrams and real-world execution by combining **bpmn-js** (for modeling) and **durable-workflow** (for resilient, suspendable background execution).

## **Key Features**

* **No Heavy External Infrastructure:** Runs entirely on your existing Laravel queue system (database, Redis, SQS, etc.).  
* **Durable Execution:** Workflows are resilient. If a server crashes or restarts mid-process, the engine resumes exactly where it left off.  
* **Embedded Visual Designer:** A beautiful, responsive drag-and-drop designer powered by bpmn-js is built right into your Laravel app.  
* **Durable Parallel Execution:** True execution of parallel branches (AND splits/joins) utilizing PHP child-workflow fibers to bypass generator serialization constraints.  
* **Human-in-the-Loop (userTask):** Pause execution indefinitely to wait for external human input (signals), then resume automatically.  
* **Service Orchestration (serviceTask):** Map visual canvas elements directly to native Laravel Activity classes.

## **Technical Dependencies**

To run smoothly, this package is built upon the following technical core:

* **PHP 8.2+:** Leveraging modern PHP features and execution reliability.
* **Illuminate Support (^11.0 || ^12.0 || ^13.0):** Native integration with the Laravel framework ecosystem (database migrations, Service Providers, and Artisan commands).
* **Symfony Expression Language (^7.0):** Used for evaluating sandbox-safe expressions in conditional Exclusive Gateways (exclusiveGateway).
* **Durable Workflow (^1.0):** The robust PHP coroutine framework that provides suspendable workflow engines, state persistence, child workflows, and queue orchestration.
* **bpmn-js (via CDN):** The frontend interactive modeling library that renders the visual drag-and-drop design canvas.

## **How It Works Under the Hood**

The package splits responsibilities into three distinct, elegant components:

1. **The Parser (BpmnParserService):** Parses the standard BPMN 2.0 XML schema exported by the frontend canvas, building a structured graph of database models (WorkflowNode and WorkflowEdge).  
2. **The Interpreter (BpmnInterpreterWorkflow):** A single flat execution loop that steps through the parsed graph. When it encounters a tasks or a gateway, it evaluates expressions, schedules activities, or suspends itself.  
3. **The Sync Barrier (Parallel Gateways):** When encountering a parallel split, the interpreter spawns autonomous **Child Workflows** for each branch. They execute concurrently and reunite at the converging join gateway, safely avoiding PHP's native Generator serialization constraints.

## **Installation**

### **1. Require the Package**

For local development, you can link the package to a host application by adding a path repository to your host's composer.json:  

```json
"repositories": [  
    {  
        "type": "path",  
        "url": "../bpmn-engine",  
        "options": {  
            "symlink": true  
        }  
    }  
],
```

Then require the package:  

```bash
composer require "matthewwegner/bpmn-engine" "@dev"
```

### **2. Run the Migrations & Auto-Discovery**

The package includes database models for managing workflow definitions, versions, nodes, and edges. Run the migrations to initialize them:  

```bash
php artisan migrate
```

Publish the package configuration file to map your visual tasks to concrete PHP code:  

```bash
php artisan vendor:publish --tag="bpmn-engine-config"
```

## **Configuration**

Open config/bpmn-engine.php. This file maps the string identifiers in your visual serviceTask (the implementation field on the canvas) to your native application's Workflow Activity classes.  

```php
return [  
    'activities' => [  
        'send_welcome_email' => \\App\\Workflows\\Activities\\SendWelcomeEmailActivity::class,  
        'calculate_taxes'    => \\App\\Workflows\\Activities\\CalculateTaxesActivity::class,  
    ],  
];
```

## **Usage Guide**

### **Creating and Designing a Workflow**

1. Navigate to your app's route: http://your-app.test/bpmn/workflows.  
2. Give your workflow a name and unique key (e.g., onboard-user).  
3. Drag and drop your start event, tasks, and end event.  
4. Set the implementation keys on your service tasks to match your config/bpmn-engine.php mapping.  
5. Click **Save & Compile**.

### **Launching an Execution**

To dispatch a compiled workflow from anywhere in your Laravel application:  

```php
use MatthewWegner\\BpmnEngine\\Models\\WorkflowDefinition;  
use MatthewWegner\\BpmnEngine\\Workflows\\BpmnInterpreterWorkflow;  
use Workflow\\WorkflowStub;

// 1. Load the compiled layout  
$definition = WorkflowDefinition::where('key', 'onboard-user')->first();  
$latestVersion = $definition->versions()->latest('version')->first();

// 2. Dispatch the durable background job  
$workflow = WorkflowStub::make(BpmnInterpreterWorkflow::class);  
$workflow->start($latestVersion->id, [  
    'user_id' => 42,  
    'email'   => 'user@example.com'  
]);
```

Ensure your queue workers are running to execute the tasks:  

```bash
php artisan queue:work
```

## **Acknowledgments**

This package would not be possible without the incredible work of the open-source community:

* bpmn-js by bpmn.io / Camunda: The gold standard for web-based BPMN 2.0 visualization and modeling.
* durable-workflow: The elegant coroutine orchestration layer that makes suspendable, durable PHP processes a reality.
* Symfony & Laravel: For providing the ultimate foundation of modern enterprise PHP development.

## **License**

This package is open-source software licensed under the [MIT License](https://opensource.org/license/mit).