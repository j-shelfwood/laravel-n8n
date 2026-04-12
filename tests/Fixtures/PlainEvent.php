<?php

namespace Shelfwood\N8n\Tests\Fixtures;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * An event without HasN8nTrigger — should be ignored by the wildcard listener.
 */
class PlainEvent
{
    use Dispatchable;

    public function __construct(public string $data = 'plain') {}
}
