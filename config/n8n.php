<?php

return [

    /*
    |--------------------------------------------------------------------------
    | n8n API Configuration
    |--------------------------------------------------------------------------
    |
    | Base URL and API key for the n8n instance. When N8N_URL is empty,
    | the integration is disabled — events still fire but the webhook
    | dispatch job skips the HTTP call gracefully.
    |
    */

    'api' => [
        'url' => env('N8N_URL', ''),
        'key' => env('N8N_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    */

    'workflows' => [
        'timeout' => (int) env('N8N_WORKFLOW_TIMEOUT', 10),
        'retry_attempts' => 3,
        'retry_delay' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Discovery
    |--------------------------------------------------------------------------
    |
    | Directories to scan for event classes that use the HasN8nTrigger trait.
    | The Filament status page uses these to show the event→workflow mapping.
    | Paths are relative to base_path().
    |
    */

    'event_directories' => [
        'app/Events',
        'modules/*/Events',
    ],

];
