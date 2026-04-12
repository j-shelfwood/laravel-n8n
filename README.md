# laravel-n8n

Tag-based n8n webhook integration for Laravel. Fire events in your app, auto-dispatch to matching n8n workflows. Zero configuration per workflow — just tag your n8n workflows and the package handles the rest.

## How it works

1. Add the `HasN8nTrigger` trait to any Laravel Event class
2. The event auto-generates a tag from its class name (e.g. `OrderCompleted` → `app:order-completed`)
3. When the event fires, the package finds all active n8n workflows tagged with `app:order-completed`
4. It POSTs the event payload to each workflow's webhook URL
5. Fire-and-forget — your app doesn't depend on n8n being available

## Installation

```bash
composer require shelfwood/laravel-n8n
```

The service provider auto-discovers. Publish the config:

```bash
php artisan vendor:publish --tag=n8n-config
```

Add to your `.env`:

```env
N8N_URL=https://your-n8n-instance.com
N8N_API_KEY=your-api-key
```

## Usage

### 1. Create an event with the trait

```php
use Illuminate\Foundation\Events\Dispatchable;
use Shelfwood\N8n\Traits\HasN8nTrigger;

class OrderCompleted
{
    use Dispatchable, HasN8nTrigger;

    public function __construct(
        public string $orderId,
        public float $total,
    ) {}

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'total' => $this->total,
        ];
    }
}
```

### 2. Fire the event normally

```php
event(new OrderCompleted($order->id, $order->total));
```

### 3. Create an n8n workflow with matching tag

In your n8n instance, create a workflow with:
- A **Webhook** trigger node
- A tag matching the event: `app:order-completed`

The package finds the workflow by tag and POSTs:

```json
{
    "event": "App\\Events\\OrderCompleted",
    "timestamp": "2026-04-13T00:00:00+00:00",
    "tags": ["app:order-completed"],
    "data": {
        "order_id": "abc-123",
        "total": 99.95
    }
}
```

### Custom tags

Override the auto-generated tag:

```php
$event = new OrderCompleted($id, $total);
$event->setN8nTags(['custom:high-value-order']);
event($event);
```

## Filament Status Page

If your project uses [Filament](https://filamentphp.com), register the status page in your `AdminPanelProvider`:

```php
use Shelfwood\N8n\Filament\Pages\N8nStatus;

->pages([
    N8nStatus::class,
])
```

This shows:
- n8n connection health
- All discovered events with their tags
- Which n8n workflows are connected to which events

## Configuration

```php
// config/n8n.php

return [
    'api' => [
        'url' => env('N8N_URL', ''),
        'key' => env('N8N_API_KEY', ''),
    ],
    'workflows' => [
        'timeout' => (int) env('N8N_WORKFLOW_TIMEOUT', 10),
        'retry_attempts' => 3,
        'retry_delay' => 5,
    ],
    // Directories scanned by the Filament status page
    'event_directories' => [
        'app/Events',
    ],
];
```

## Graceful degradation

- **No `N8N_URL`** → events fire normally but no webhooks are dispatched
- **n8n is down** → the queued job retries 3 times, then logs and moves on
- **No matching workflow** → the job silently returns (no error)

## Testing

In your test suite, set `N8N_URL` to empty in `phpunit.xml`:

```xml
<env name="N8N_URL" value=""/>
```

This disables webhook dispatch. Your n8n-specific tests can override:

```php
config(['n8n.api.url' => 'https://n8n.test']);
Queue::fake();

event(new OrderCompleted('123', 50.00));

Queue::assertPushed(DispatchN8nWebhook::class);
```

## License

MIT
