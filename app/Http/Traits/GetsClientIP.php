<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait GetsClientIP
{
    /**
     * Get the client's IP address, with optional Cloudflare proxy support.
     * Only trusts CF-Connecting-IP when the request originates from a known Cloudflare IP.
     */
    private function getClientIP(Request $request): string
    {
        if (config('app.use_cloudflare_ip') && $request->hasHeader('CF-Connecting-IP')) {
            $remoteIp = $request->ip() ?? '';

            if ($this->isCloudflareIp($remoteIp)) {
                return $request->header('CF-Connecting-IP') ?? '';
            }
        }

        return $request->ip() ?? '';
    }

    /**
     * Check if the given IP is within Cloudflare's published IP ranges.
     * Ranges are cached for 24 hours and refreshed periodically.
     */
    private function isCloudflareIp(string $ip): bool
    {
        $ranges = Cache::get('cloudflare_ip_ranges', []);

        foreach ($ranges as $range) {
            if ($this->ipInCidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address falls within a CIDR range.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
