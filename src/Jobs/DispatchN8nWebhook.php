<?php

namespace Shelfwood\N8n\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Shelfwood\N8n\Services\N8nService;
use Throwable;

/**
 * Dispatches an event payload to all n8n workflows matching the event's tags.
 *
 * Fire-and-forget: the app doesn't depend on n8n being available. If n8n is
 * down, the job logs at debug level and completes — no retries, no errors.
 */
class DispatchN8nWebhook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @param  array<string, mixed>  $payload  Event data to send
     * @param  array<int, string>  $tags  n8n workflow tags to target
     */
    public function __construct(
        public array $payload,
        public array $tags,
    ) {}

    /**
     * Find matching n8n workflows and POST the payload to their webhooks.
     *
     * Catches all exceptions so n8n downtime never produces error logs or retries.
     * This is fire-and-forget — the app must never depend on n8n availability.
     */
    public function handle(N8nService $n8nService): void
    {
        try {
            $webhookUrls = $n8nService->getWebhookUrlsByTags($this->tags);
        } catch (Throwable $e) {
            Log::debug('n8n unreachable, skipping webhook dispatch', [
                'tags' => $this->tags,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($webhookUrls)) {
            return;
        }

        foreach ($webhookUrls as $url) {
            try {
                $n8nService->sendWebhook($url, $this->payload);
            } catch (Throwable $e) {
                Log::debug('n8n webhook delivery failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
