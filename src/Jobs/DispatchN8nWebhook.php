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
 * down, the job retries 3 times then logs the failure — no user impact.
 */
class DispatchN8nWebhook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum number of attempts */
    public int $tries = 3;

    /** @var int Seconds between retries */
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
     * Find matching n8n workflows and POST the payload to their webhooks.
     */
    public function handle(N8nService $n8nService): void
    {
        $webhookUrls = $n8nService->getWebhookUrlsByTags($this->tags);

        if (empty($webhookUrls)) {
            return;
        }

        foreach ($webhookUrls as $url) {
            $n8nService->sendWebhook($url, $this->payload);
        }
    }

    /**
     * Handle permanent failure after all retries exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::warning('n8n webhook dispatch failed permanently', [
            'tags' => $this->tags,
            'error' => $exception->getMessage(),
        ]);
    }
}
