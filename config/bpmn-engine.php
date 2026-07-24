<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Mappings
    |--------------------------------------------------------------------------
    |
    | This array maps the string identifier found in a BPMN <serviceTask> 
    | (the 'implementation' attribute) to the fully qualified class name 
    | of the Activity in your host application.
    |
    */
    'activities' => [
        // Example:
        // 'calculate_tax' => \App\Workflows\Activities\CalculateTaxActivity::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trigger Mappings
    |--------------------------------------------------------------------------
    |
    | Map the string identifier found in a BPMN <startEvent> 
    | to the fully qualified class name of the Laravel Event that triggers it.
    |
    */
    'triggers' => [
        // 'custody_log_created' => \App\Events\CustodyLogCreated::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Routing Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every BPMN Engine route. You can
    | add your own middleware here to protect the designer and dashboard
    | behind a login, such as the 'auth' middleware.
    |
    */
    'middleware' => ['web'],
    'api_middleware' => ['api'],
];