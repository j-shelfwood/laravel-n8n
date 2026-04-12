<?php

namespace Shelfwood\N8n\Tests\Fixtures;

use Illuminate\Foundation\Events\Dispatchable;
use Shelfwood\N8n\Traits\HasN8nTrigger;

class TestEvent
{
    use Dispatchable, HasN8nTrigger;

    public function __construct(
        public string $message,
        public int $count,
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'count' => $this->count,
        ];
    }
}
