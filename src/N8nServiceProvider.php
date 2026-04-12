<?php

namespace Shelfwood\N8n;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Shelfwood\N8n\Jobs\DispatchN8nWebhook;
use Shelfwood\N8n\Services\N8nService;
use Shelfwood\N8n\Traits\HasN8nTrigger;

class N8nServiceProvider extends ServiceProvider
{
    /**
     * Register the N8nService singleton and merge config.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/n8n.php', 'n8n');

        $this->app->singleton(N8nService::class);
    }

    /**
     * Boot the wildcard event listener and publish assets.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/n8n.php' => config_path('n8n.php'),
        ], 'n8n-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'n8n');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/n8n'),
        ], 'n8n-views');

        $this->wireN8nEvents();
    }

    /**
     * Auto-dispatch n8n webhooks for any event using the HasN8nTrigger trait.
     *
     * Listens to all events via wildcard. When an event has the trait and
     * non-empty tags, a queued DispatchN8nWebhook job is dispatched.
     * Checks config per-event so runtime changes (e.g. in tests) take effect.
     */
    private function wireN8nEvents(): void
    {
        Event::listen('*', function (string $eventName, array $data): void {
            if (blank(config('n8n.api.url'))) {
                return;
            }

            $event = $data[0] ?? null;

            if (! is_object($event)) {
                return;
            }

            if (! in_array(HasN8nTrigger::class, class_uses_recursive($event), true)) {
                return;
            }

            if (! method_exists($event, 'hasN8nTrigger') || ! $event->hasN8nTrigger()) {
                return;
            }

            $payload = method_exists($event, 'getN8nPayload') ? $event->getN8nPayload() : [];
            $tags = method_exists($event, 'getN8nTags') ? $event->getN8nTags() : [];

            if (! empty($tags)) {
                DispatchN8nWebhook::dispatch($payload, $tags);
            }
        });
    }
}
