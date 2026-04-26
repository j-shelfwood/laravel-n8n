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
     * Maximum retry attempts before the job is marked as permanently failed.
     *
     * Conservative default — n8n webhooks are usually idempotent on the
     * receiving side (workflows dedupe by payload), so a small retry budget
     * is safe. Override per-application by extending the job.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retry attempts.
     */
    public int $backoff = 5;

    /**
     * @param  array<string, mixed>  $payload  Event data to send
     * @param  array<int, string>  $tags  n8n workflow tags to target
     */
    public function __construct(
        public array $payload,
        public array $tags,
    ) {}

    /**
     * Permanent-failure hook fired after `$tries` exhausted.
     *
     * Logs at warning level so operators see the dispatch was lost. The
     * package never throws into the queue worker — `handle()` already swallows
     * connection errors — so this only fires when an explicit retryable
     * exception was rethrown by a subclass.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::warning('n8n webhook dispatch failed permanently', [
            'tags' => $this->tags,
            'error' => $exception->getMessage(),
        ]);
    }

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
