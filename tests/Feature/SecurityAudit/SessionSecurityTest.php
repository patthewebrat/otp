<?php

namespace Tests\Feature\SecurityAudit;

use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    public function test_session_secure_cookie_config_defaults_to_true(): void
    {
        // Read the config file directly to verify the default value
        $sessionConfig = require base_path('config/session.php');

        // The env() call will return the ENV value at runtime,
        // so we verify the config file source has the correct default
        $configSource = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_SECURE_COOKIE', true)",
            $configSource,
            'Session secure cookie should default to true when env var is not set'
        );
    }

    public function test_session_encryption_config_defaults_to_true(): void
    {
        $configSource = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_ENCRYPT', true)",
            $configSource,
            'Session encryption should default to true when env var is not set'
        );
    }
}
