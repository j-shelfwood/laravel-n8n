<?php

use Shelfwood\N8n\Tests\Fixtures\TestEvent;
use Shelfwood\N8n\Traits\HasN8nTrigger;

test('generates kebab-case tag from class name', function () {
    $event = new TestEvent('hello', 1);

    expect($event->getN8nTags())->toBe(['app:test-event']);
});

test('hasN8nTrigger returns true when tags exist', function () {
    $event = new TestEvent('hello', 1);

    expect($event->hasN8nTrigger())->toBeTrue();
});

test('custom tags override auto-generated tags', function () {
    $event = new TestEvent('hello', 1);
    $event->setN8nTags(['custom:my-tag']);

    expect($event->getN8nTags())->toBe(['custom:my-tag']);
});

test('toArray returns event data', function () {
    $event = new TestEvent('hello', 42);

    expect($event->toArray())->toBe([
        'message' => 'hello',
        'count' => 42,
    ]);
});

test('getN8nPayload includes metadata and data', function () {
    $event = new TestEvent('payload-test', 5);
    $payload = $event->getN8nPayload();

    expect($payload)
        ->toHaveKeys(['event', 'timestamp', 'tags', 'data'])
        ->and($payload['event'])->toBe(TestEvent::class)
        ->and($payload['tags'])->toBe(['app:test-event'])
        ->and($payload['data'])->toBe(['message' => 'payload-test', 'count' => 5])
        ->and($payload['timestamp'])->toBeString();
});

test('generateDefaultN8nTagsForClass works statically', function () {
    $tags = HasN8nTrigger::generateDefaultN8nTagsForClass(TestEvent::class);

    expect($tags)->toBe(['app:test-event']);
});

test('default toArray from trait returns empty array', function () {
    // Create anonymous class using trait but NOT overriding toArray
    $event = new class
    {
        use HasN8nTrigger;
    };

    expect($event->toArray())->toBe([]);
});

test('tag prefix is configurable via n8n.tag_prefix', function () {
    config(['n8n.tag_prefix' => 'staging:']);

    $event = new TestEvent('hi', 1);

    expect($event->getN8nTags())->toBe(['staging:test-event']);
});

test('tag prefix override applies to static generator too', function () {
    config(['n8n.tag_prefix' => 'prod:']);

    expect(HasN8nTrigger::generateDefaultN8nTagsForClass(TestEvent::class))
        ->toBe(['prod:test-event']);
});

test('tag prefix falls back to app: when config is missing', function () {
    config(['n8n.tag_prefix' => null]);

    $event = new TestEvent('hi', 1);

    expect($event->getN8nTags())->toBe(['app:test-event']);
});
