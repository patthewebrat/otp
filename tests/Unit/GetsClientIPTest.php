<?php

namespace Tests\Unit;

use App\Http\Traits\GetsClientIP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GetsClientIPTest extends TestCase
{
    private object $trait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = new class
        {
            use GetsClientIP {
                ipInCidr as public;
                isCloudflareIp as public;
                getClientIP as public;
            }
        };
    }

    // --- ipInCidr tests ---

    public function test_ip_in_cidr_matches_ip_within_range(): void
    {
        $this->assertTrue($this->trait->ipInCidr('192.168.1.50', '192.168.1.0/24'));
    }

    public function test_ip_in_cidr_rejects_ip_outside_range(): void
    {
        $this->assertFalse($this->trait->ipInCidr('10.0.0.1', '192.168.1.0/24'));
    }

    public function test_ip_in_cidr_matches_exact_ip_without_cidr(): void
    {
        $this->assertTrue($this->trait->ipInCidr('10.0.0.1', '10.0.0.1'));
    }

    public function test_ip_in_cidr_rejects_different_exact_ip(): void
    {
        $this->assertFalse($this->trait->ipInCidr('10.0.0.2', '10.0.0.1'));
    }

    public function test_ip_in_cidr_slash_32_matches_single_host(): void
    {
        $this->assertTrue($this->trait->ipInCidr('10.0.0.1', '10.0.0.1/32'));
        $this->assertFalse($this->trait->ipInCidr('10.0.0.2', '10.0.0.1/32'));
    }

    public function test_ip_in_cidr_slash_0_matches_everything(): void
    {
        $this->assertTrue($this->trait->ipInCidr('1.2.3.4', '0.0.0.0/0'));
        $this->assertTrue($this->trait->ipInCidr('255.255.255.255', '0.0.0.0/0'));
    }

    public function test_ip_in_cidr_returns_false_for_invalid_ip(): void
    {
        $this->assertFalse($this->trait->ipInCidr('not-an-ip', '192.168.1.0/24'));
    }

    public function test_ip_in_cidr_returns_false_for_invalid_subnet(): void
    {
        $this->assertFalse($this->trait->ipInCidr('192.168.1.1', 'not-a-subnet/24'));
    }

    // --- isCloudflareIp tests ---

    public function test_is_cloudflare_ip_returns_true_when_ip_in_cached_ranges(): void
    {
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20', '103.21.244.0/22']);

        $this->assertTrue($this->trait->isCloudflareIp('173.245.48.1'));
    }

    public function test_is_cloudflare_ip_returns_false_when_ip_not_in_cached_ranges(): void
    {
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20']);

        $this->assertFalse($this->trait->isCloudflareIp('10.0.0.1'));
    }

    public function test_is_cloudflare_ip_returns_false_when_no_cached_ranges(): void
    {
        Cache::forget('cloudflare_ip_ranges');

        $this->assertFalse($this->trait->isCloudflareIp('173.245.48.1'));
    }

    // --- getClientIP tests ---

    public function test_get_client_ip_returns_request_ip_when_cloudflare_disabled(): void
    {
        Config::set('app.use_cloudflare_ip', false);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '1.2.3.4',
        ]);

        $this->assertEquals('1.2.3.4', $this->trait->getClientIP($request));
    }

    public function test_get_client_ip_returns_cf_header_when_cloudflare_enabled_and_from_cf_ip(): void
    {
        Config::set('app.use_cloudflare_ip', true);
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20']);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '173.245.48.1',
            'HTTP_CF_CONNECTING_IP' => '5.6.7.8',
        ]);

        $this->assertEquals('5.6.7.8', $this->trait->getClientIP($request));
    }

    public function test_get_client_ip_ignores_cf_header_when_not_from_cf_ip(): void
    {
        Config::set('app.use_cloudflare_ip', true);
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20']);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_CF_CONNECTING_IP' => '5.6.7.8',
        ]);

        $this->assertEquals('10.0.0.1', $this->trait->getClientIP($request));
    }

    public function test_get_client_ip_returns_request_ip_when_cf_header_absent(): void
    {
        Config::set('app.use_cloudflare_ip', true);
        Cache::put('cloudflare_ip_ranges', ['173.245.48.0/20']);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '173.245.48.1',
        ]);

        $this->assertEquals('173.245.48.1', $this->trait->getClientIP($request));
    }
}
