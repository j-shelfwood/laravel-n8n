<?php

use Illuminate\Support\Facades\Queue;
use Shelfwood\N8n\Jobs\DispatchN8nWebhook;
use Shelfwood\N8n\Tests\Fixtures\PlainEvent;
use Shelfwood\N8n\Tests\Fixtures\TestEvent;

test('dispatches DispatchN8nWebhook when N8N_URL is configured', function () {
    config(['n8n.api.url' => 'https://n8n.test']);
    Queue::fake();

    event(new TestEvent('wired', 1));

    Queue::assertPushed(DispatchN8nWebhook::class, function (DispatchN8nWebhook $job) {
        return $job->tags === ['app:test-event']
            && $job->payload['data']['message'] === 'wired';
    });
});

test('does not dispatch when N8N_URL is empty', function () {
    config(['n8n.api.url' => '']);
    Queue::fake();

    event(new TestEvent('silent', 1));

    Queue::assertNotPushed(DispatchN8nWebhook::class);
});

test('ignores events without HasN8nTrigger', function () {
    config(['n8n.api.url' => 'https://n8n.test']);
    Queue::fake();

    event(new PlainEvent);

    Queue::assertNotPushed(DispatchN8nWebhook::class);
});
