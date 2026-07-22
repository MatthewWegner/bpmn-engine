# **Laravel BPMN Engine**

A lightweight, native PHP workflow orchestrator and visual designer for Laravel. This package allows you to model business processes visually using BPMN 2.0 and execute them as durable, stateful background jobs—without any external Java dependencies (like Camunda or Zeebe).

It bridges the gap between visual business diagrams and real-world execution by combining **bpmn-js** (for modeling) and **durable-workflow** (for resilient, suspendable background execution).

***Status: v0.2.0-alpha. This package is actively being developed. The core execution engine, token tracking, and manual intervention layers are operational, but it is not yet recommended for production environments.***

## **Key Features**

* **No Heavy External Infrastructure:** Runs entirely on your existing Laravel queue system (database, Redis, SQS, etc.).
* **Durable Execution:** Workflows are resilient. If a server crashes or restarts mid-process, the engine resumes exactly where it left off.
* **Embedded Visual Designer:** A beautiful, responsive drag-and-drop designer powered by bpmn-js is built right into your Laravel app.
* **Event-Driven Architecture:** Natively listen to Laravel Events to trigger workflows automatically using BPMN Message Start Events.
* **Durable Parallel Execution:** True execution of parallel branches (AND splits/joins) utilizing PHP child-workflow fibers to bypass generator serialization constraints.
* **Human-in-the-Loop (userTask):** Pause execution indefinitely to wait for external human input (signals), then resume automatically.
* **Service Orchestration (serviceTask):** Map visual canvas elements directly to native Laravel Activity classes.
* **State Projection & Live Tracking:** Separates background coroutines from UI tracking using isolated relational workflow_tokens, completely eliminating parallel race conditions.
* **Manual Interventions:** Built-in CLI and Web UI dashboard to safely suspend, resume, and halt running workflows.
* **Error Boundary Events:** Anticipate system failures by attaching boundary events to tasks, seamlessly catching exceptions and routing tokens to defined fallback paths.

## **Technical Dependencies**

To run smoothly, this package is built upon the following technical core:

* **PHP 8.3+:** Leveraging modern PHP features and execution reliability.
* **Illuminate Support (^12.0 || ^13.0):** Native integration with the Laravel framework ecosystem (database migrations, Service Providers, and Artisan commands).
* **Symfony Expression Language (^7.0):** Used for evaluating sandbox-safe expressions in conditional Exclusive Gateways (exclusiveGateway).
* **Durable Workflow (^1.0):** The robust PHP coroutine framework that provides suspendable workflow engines, state persistence, child workflows, and queue orchestration.
* **bpmn-js:** The frontend interactive modeling library that renders the visual drag-and-drop design canvas, bundled natively using Vite.

## **How It Works Under the Hood**

The package splits responsibilities into three distinct, elegant components:

1. **The Parser (BpmnParserService):** Parses the standard BPMN 2.0 XML schema exported by the frontend canvas, building a structured graph of database models (WorkflowNode and WorkflowEdge).
2. **The Interpreter (BpmnInterpreterWorkflow):** A single flat execution loop that steps through the parsed graph. When it encounters tasks or a gateway, it evaluates expressions, schedules activities, or suspends itself.
3. **The Sync Barrier (Parallel Gateways):** When encountering a parallel split, the interpreter spawns autonomous **Child Workflows** for each branch. They execute concurrently and reunite at the converging join gateway, safely avoiding PHP's native Generator serialization constraints.
4. **State Projection:** Updates to active pointers are safely managed via deterministic `sideEffects`, ensuring your `workflow_instances` and `workflow_tokens` tables are always perfectly synchronized with the background queue state.

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

### **2. Install and Initialize**

The package provides an installation command. This command will publish the necessary configuration file (bpmn-engine.php), the compiled frontend JavaScript assets, the durable-workflow queue migrations, and offer to migrate your database automatically.

```bash
php artisan bpmn:install
```

## **Quickstart: The Interactive Demo**

You can scaffold an "Order Processing" workflow directly into your host application.

```bash
php artisan bpmn:demo
```

This command will:

1. Generate a demo event trigger and a demo background activity.
2. Register them automatically in your config/bpmn-engine.php file.
3. Seed your database with a sample BPMN diagram featuring exclusive routing and a human-in-the-loop task.

You can view the generated diagram at /bpmn/workflows, and dispatch the event via php artisan tinker using the instructions provided by the command.

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

### **Defining Triggers and Activities**

The package provides Artisan commands to rapidly scaffold the code required to interact with your workflows.

**Create a Trigger:** Generates a Laravel Event that implements the required idempotency contract, allowing it to automatically launch a workflow when fired.

```bash
php artisan bpmn:make-trigger
```

**Create an Activity:** Generates a durable background activity class to execute your specific domain logic (e.g., sending an email or charging a card).

```bash
php artisan bpmn:make-activity
```

Both commands will interactively ask for an alias (e.g., send_welcome_email) and automatically register the mapping in your config/bpmn-engine.php file.

### **Creating and Designing a Workflow**

1. Navigate to your app's route: http://your-app.test/bpmn/workflows.
2. Give your workflow a name and unique key (e.g., onboard-user).
3. Drag and drop your start event, tasks, and end event.
    * Use a **Message Start Event** to listen for a Trigger alias.
    * Use a **Service Task** and the properties panel to map to an Activity alias.
4. Set the implementation keys on your service tasks to match your config/bpmn-engine.php mapping.
5. Click **Save & Compile**.

### **Element Templates**

To make designing easier for non-technical users, you can generate custom process nodes (Element Templates) for the properties panel.

```bash
php artisan bpmn:make-template "Send Welcome Email"
```

This generates a JSON configuration file in your host application (resources/bpmn/templates/). When applied to a Service Task in the visual designer, it automatically hides complex technical fields and wires up the correct backend implementation class.

### **Managing Instances & Tokens**

You can view, suspend, resume, and halt running workflows from the included dashboard or the command line.

#### Web Dashboard:

Navigate to /bpmn/instances to see a real-time list of active processes and their concurrent token locations.

#### Command Line Controls:

```bash
# List all recent instances
php artisan bpmn:instance list

# Intervene in a specific execution
php artisan bpmn:instance suspend {id}
php artisan bpmn:instance resume {id}
php artisan bpmn:instance halt {id}
```

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