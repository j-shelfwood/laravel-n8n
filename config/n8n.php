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
        // TTL in seconds for the cached workflow list. Every DispatchN8nWebhook
        // job refetches the entire workflow set to filter by tag — caching
        // collapses N events × M workflows down to a single fetch per TTL
        // window. Set to 0 to disable.
        'cache_ttl' => (int) env('N8N_WORKFLOWS_CACHE_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tag Prefix
    |--------------------------------------------------------------------------
    |
    | Default tag prefix for events using HasN8nTrigger. Set to an env-derived
    | value (e.g. `staging:` / `prod:`) to discriminate workflows on a single
    | shared n8n instance. Tags emitted look like `<prefix><kebab-event-name>`.
    |
    */

    'tag_prefix' => env('N8N_TAG_PREFIX', 'app:'),

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
