<?php

namespace Tests\Feature\SecurityAudit;

use Tests\TestCase;

class EnvDefaultsTest extends TestCase
{
    public function test_env_example_has_debug_disabled(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $envExample, '.env.example should default APP_DEBUG to false');
    }
}
