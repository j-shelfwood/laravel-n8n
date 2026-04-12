<?php

use Illuminate\Support\Facades\Http;
use Shelfwood\N8n\Services\N8nService;

test('getWebhookUrlsByTags returns URLs for matching active workflows', function () {
    config(['n8n.api.url' => 'https://n8n.test']);

    Http::fake([
        'n8n.test/api/v1/workflows' => Http::response([
            'data' => [
                [
                    'id' => '1',
                    'name' => 'Alert workflow',
                    'active' => true,
                    'tags' => [['name' => 'app:redesign-completed']],
                    'nodes' => [
                        [
                            'type' => 'n8n-nodes-base.webhook',
                            'parameters' => ['path' => 'abc123'],
                        ],
                    ],
                ],
                [
                    'id' => '2',
                    'name' => 'Inactive workflow',
                    'active' => false,
                    'tags' => [['name' => 'app:redesign-completed']],
                    'nodes' => [
                        [
                            'type' => 'n8n-nodes-base.webhook',
                            'parameters' => ['path' => 'def456'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new N8nService;
    $urls = $service->getWebhookUrlsByTags(['app:redesign-completed']);

    expect($urls)->toBe(['https://n8n.test/webhook/abc123']);
});

test('getWebhookUrlsByTags returns empty for unmatched tags', function () {
    config(['n8n.api.url' => 'https://n8n.test']);

    Http::fake([
        'n8n.test/api/v1/workflows' => Http::response([
            'data' => [
                [
                    'id' => '1',
                    'active' => true,
                    'tags' => [['name' => 'app:other']],
                    'nodes' => [],
                ],
            ],
        ]),
    ]);

    $service = new N8nService;
    $urls = $service->getWebhookUrlsByTags(['app:redesign-completed']);

    expect($urls)->toBe([]);
});

test('getWebhookUrlsByTags returns empty for empty tags input', function () {
    $service = new N8nService;

    expect($service->getWebhookUrlsByTags([]))->toBe([]);
});

test('sendWebhook posts payload to URL', function () {
    config(['n8n.workflows.timeout' => 5]);

    Http::fake([
        'n8n.test/webhook/abc' => Http::response([], 200),
    ]);

    $service = new N8nService;
    $service->sendWebhook('https://n8n.test/webhook/abc', ['event' => 'test']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://n8n.test/webhook/abc'
            && $request->data()['event'] === 'test';
    });
});

test('getWorkflows returns workflow list', function () {
    config(['n8n.api.url' => 'https://n8n.test']);

    Http::fake([
        'n8n.test/api/v1/workflows' => Http::response([
            'data' => [
                ['id' => '1', 'name' => 'Workflow A', 'active' => true],
                ['id' => '2', 'name' => 'Workflow B', 'active' => false],
            ],
        ]),
    ]);

    $service = new N8nService;
    $workflows = $service->getWorkflows();

    expect($workflows)->toHaveCount(2)
        ->and($workflows[0]['name'])->toBe('Workflow A');
});

test('findWorkflowsByTag filters by tag name', function () {
    config(['n8n.api.url' => 'https://n8n.test']);

    Http::fake([
        'n8n.test/api/v1/workflows' => Http::response([
            'data' => [
                ['id' => '1', 'tags' => [['name' => 'app:match']]],
                ['id' => '2', 'tags' => [['name' => 'app:other']]],
            ],
        ]),
    ]);

    $service = new N8nService;
    $matches = $service->findWorkflowsByTag('app:match');

    expect($matches)->toHaveCount(1)
        ->and($matches[0]['id'])->toBe('1');
});
