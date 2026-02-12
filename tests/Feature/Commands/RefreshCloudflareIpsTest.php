<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefreshCloudflareIpsTest extends TestCase
{
    public function test_fetches_and_caches_ip_ranges(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response("173.245.48.0/20\n103.21.244.0/22\n"),
            'www.cloudflare.com/ips-v6/' => Http::response("2400:cb00::/32\n2606:4700::/32\n"),
        ]);

        $this->artisan('cloudflare:refresh-ips')->assertSuccessful();

        $ranges = Cache::get('cloudflare_ip_ranges');
        $this->assertContains('173.245.48.0/20', $ranges);
        $this->assertContains('103.21.244.0/22', $ranges);
        $this->assertContains('2400:cb00::/32', $ranges);
        $this->assertContains('2606:4700::/32', $ranges);
    }

    public function test_output_includes_count_of_cached_ranges(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response("173.245.48.0/20\n103.21.244.0/22\n"),
            'www.cloudflare.com/ips-v6/' => Http::response("2400:cb00::/32\n"),
        ]);

        $this->artisan('cloudflare:refresh-ips')
            ->expectsOutputToContain('Cached 3 Cloudflare IP ranges.');
    }

    public function test_ipv4_fetch_failure_returns_failure_exit_code(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response('', 500),
            'www.cloudflare.com/ips-v6/' => Http::response("2400:cb00::/32\n"),
        ]);

        $this->artisan('cloudflare:refresh-ips')->assertFailed();
    }

    public function test_ipv6_fetch_failure_is_non_fatal(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response("173.245.48.0/20\n"),
            'www.cloudflare.com/ips-v6/' => Http::response('', 500),
        ]);

        $this->artisan('cloudflare:refresh-ips')->assertSuccessful();

        $ranges = Cache::get('cloudflare_ip_ranges');
        $this->assertContains('173.245.48.0/20', $ranges);
        $this->assertCount(1, $ranges);
    }

    public function test_filters_empty_lines_from_responses(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response("\n173.245.48.0/20\n\n103.21.244.0/22\n\n"),
            'www.cloudflare.com/ips-v6/' => Http::response("\n\n"),
        ]);

        $this->artisan('cloudflare:refresh-ips')->assertSuccessful();

        $ranges = Cache::get('cloudflare_ip_ranges');
        $this->assertCount(2, $ranges);
    }

    public function test_cache_key_is_cloudflare_ip_ranges(): void
    {
        Http::fake([
            'www.cloudflare.com/ips-v4/' => Http::response("173.245.48.0/20\n"),
            'www.cloudflare.com/ips-v6/' => Http::response(''),
        ]);

        Cache::forget('cloudflare_ip_ranges');

        $this->artisan('cloudflare:refresh-ips');

        $this->assertTrue(Cache::has('cloudflare_ip_ranges'));
    }

    public function test_handles_network_exception_gracefully(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->artisan('cloudflare:refresh-ips')
            ->assertFailed()
            ->expectsOutputToContain('Failed to refresh Cloudflare IPs');
    }
}
