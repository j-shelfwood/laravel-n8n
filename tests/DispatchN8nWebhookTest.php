<?php

use Illuminate\Support\Facades\Log;
use Shelfwood\N8n\Jobs\DispatchN8nWebhook;
use Shelfwood\N8n\Services\N8nService;

test('calls sendWebhook for each matched URL', function () {
    $service = Mockery::mock(N8nService::class);
    $service->shouldReceive('getWebhookUrlsByTags')
        ->with(['app:test'])
        ->once()
        ->andReturn(['https://n8n.test/webhook/a', 'https://n8n.test/webhook/b']);

    $service->shouldReceive('sendWebhook')->twice();

    $job = new DispatchN8nWebhook(['event' => 'test'], ['app:test']);
    $job->handle($service);
});

test('does nothing when no URLs match', function () {
    $service = Mockery::mock(N8nService::class);
    $service->shouldReceive('getWebhookUrlsByTags')->andReturn([]);
    $service->shouldNotReceive('sendWebhook');

    $job = new DispatchN8nWebhook(['event' => 'test'], ['app:unmatched']);
    $job->handle($service);
});

test('logs warning on permanent failure', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'n8n webhook dispatch failed'));

    $job = new DispatchN8nWebhook([], ['app:test']);
    $job->failed(new RuntimeException('timeout'));
});

test('has correct retry configuration', function () {
    $job = new DispatchN8nWebhook([], []);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(5);
});
