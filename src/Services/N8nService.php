<?php

namespace Shelfwood\N8n\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the n8n REST API — finds tagged workflows and dispatches webhooks.
 *
 * Uses Laravel's HTTP client. All methods are fail-safe: API errors are logged
 * but never thrown to callers unless explicitly needed.
 */
class N8nService
{
    /**
     * Get all workflows from the n8n instance.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWorkflows(): array
    {
        $response = $this->apiRequest('get', 'api/v1/workflows');

        return $response['data'] ?? [];
    }

    /**
     * Find active workflows matching any of the given tags, extract their webhook URLs.
     *
     * @param  array<int, string>  $tags
     * @return array<int, string>
     */
    public function getWebhookUrlsByTags(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $workflows = $this->getWorkflows();
        $urls = [];

        foreach ($workflows as $workflow) {
            if (! ($workflow['active'] ?? false)) {
                continue;
            }

            $workflowTags = $this->extractTagNames($workflow['tags'] ?? []);

            if (empty(array_intersect($tags, $workflowTags))) {
                continue;
            }

            foreach ($workflow['nodes'] ?? [] as $node) {
                if (($node['type'] ?? '') !== 'n8n-nodes-base.webhook') {
                    continue;
                }

                $webhookPath = $node['parameters']['path'] ?? $node['webhookId'] ?? null;

                if ($webhookPath === null) {
                    continue;
                }

                $baseUrl = rtrim(config('n8n.api.url'), '/');
                $urls[] = $baseUrl.'/webhook/'.ltrim($webhookPath, '/');
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Find workflows with a specific tag.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findWorkflowsByTag(string $tag): array
    {
        $workflows = $this->getWorkflows();
        $matches = [];

        foreach ($workflows as $workflow) {
            $workflowTags = $this->extractTagNames($workflow['tags'] ?? []);

            if (in_array($tag, $workflowTags, true)) {
                $matches[] = $workflow;
            }
        }

        return $matches;
    }

    /**
     * POST a payload to a webhook URL.
     */
    public function sendWebhook(string $url, array $data): void
    {
        $response = Http::timeout(config('n8n.workflows.timeout', 10))
            ->post($url, $data);

        if (! $response->successful()) {
            Log::warning('n8n webhook returned non-200', [
                'url' => $url,
                'status' => $response->status(),
            ]);
        }
    }

    /**
     * Make an authenticated API request to the n8n instance.
     *
     * @return array<string, mixed>
     */
    private function apiRequest(string $method, string $path): array
    {
        $baseUrl = rtrim(config('n8n.api.url'), '/');
        $apiKey = config('n8n.api.key');

        $response = Http::timeout(config('n8n.workflows.timeout', 10))
            ->withHeaders(array_filter([
                'X-N8N-API-KEY' => $apiKey ?: null,
            ]))
            ->{$method}("{$baseUrl}/{$path}");

        if (! $response->successful()) {
            throw new \RuntimeException("n8n API error: HTTP {$response->status()} on {$path}");
        }

        return $response->json() ?? [];
    }

    /**
     * Extract tag name strings from n8n's tag objects.
     *
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    private function extractTagNames(array $tags): array
    {
        $names = [];

        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $names[] = $tag;
            }

            if (is_array($tag) && isset($tag['name'])) {
                $names[] = (string) $tag['name'];
            }
        }

        return $names;
    }
}
