<?php

namespace Shelfwood\N8n\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shelfwood\N8n\N8nServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [N8nServiceProvider::class];
    }
}
