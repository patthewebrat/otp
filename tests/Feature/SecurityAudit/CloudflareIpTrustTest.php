<?php

namespace Tests\Feature\SecurityAudit;

use App\Http\Traits\GetsClientIP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CloudflareIpTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_cf_header_ignored_when_not_from_cloudflare_ip(): void
    {
        config(['app.use_cloudflare_ip' => true]);

        // Cache a fake Cloudflare IP range that doesn't include 127.0.0.1
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20', '103.21.244.0/22'], 86400);

        $controller = new class
        {
            use GetsClientIP;

            public function getIP(Request $request): string
            {
                return $this->getClientIP($request);
            }
        };

        $request = Request::create('/test', 'GET');
        $request->headers->set('CF-Connecting-IP', '1.2.3.4');
        // Simulate request coming from a non-Cloudflare IP
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $ip = $controller->getIP($request);

        // Should NOT trust CF-Connecting-IP since the request didn't come from Cloudflare
        $this->assertNotEquals('1.2.3.4', $ip, 'Should not trust CF-Connecting-IP from non-Cloudflare source');
    }

    public function test_cf_header_trusted_when_from_cloudflare_ip(): void
    {
        config(['app.use_cloudflare_ip' => true]);

        // Cache Cloudflare IP ranges including the test IP
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20', '127.0.0.0/8'], 86400);

        $controller = new class
        {
            use GetsClientIP;

            public function getIP(Request $request): string
            {
                return $this->getClientIP($request);
            }
        };

        $request = Request::create('/test', 'GET');
        $request->headers->set('CF-Connecting-IP', '1.2.3.4');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $ip = $controller->getIP($request);

        $this->assertEquals('1.2.3.4', $ip, 'Should trust CF-Connecting-IP from Cloudflare source');
    }

    public function test_cf_header_ignored_when_cloudflare_disabled(): void
    {
        config(['app.use_cloudflare_ip' => false]);

        $controller = new class
        {
            use GetsClientIP;

            public function getIP(Request $request): string
            {
                return $this->getClientIP($request);
            }
        };

        $request = Request::create('/test', 'GET');
        $request->headers->set('CF-Connecting-IP', '1.2.3.4');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $ip = $controller->getIP($request);

        $this->assertNotEquals('1.2.3.4', $ip);
    }
}
