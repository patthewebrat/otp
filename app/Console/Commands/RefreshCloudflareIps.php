<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RefreshCloudflareIps extends Command
{
    protected $signature = 'cloudflare:refresh-ips';

    protected $description = 'Fetch and cache Cloudflare IP ranges for trusted proxy validation';

    public function handle(): int
    {
        try {
            $ipv4Response = Http::timeout(10)->get('https://www.cloudflare.com/ips-v4/');

            if ($ipv4Response->failed()) {
                $this->error('Failed to fetch Cloudflare IPv4 ranges.');

                return self::FAILURE;
            }

            $ranges = array_filter(
                array_map('trim', explode("\n", $ipv4Response->body()))
            );

            // Optionally include IPv6
            $ipv6Response = Http::timeout(10)->get('https://www.cloudflare.com/ips-v6/');
            if ($ipv6Response->successful()) {
                $ipv6Ranges = array_filter(
                    array_map('trim', explode("\n", $ipv6Response->body()))
                );
                $ranges = array_merge($ranges, $ipv6Ranges);
            }

            // Cache for 48 hours (command should run daily via scheduler)
            Cache::put('cloudflare_ip_ranges', $ranges, 172800);

            $this->info('Cached ' . count($ranges) . ' Cloudflare IP ranges.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to refresh Cloudflare IPs: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
