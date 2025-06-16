<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

trait GetsClientIP
{
    /**
     * Get the client's IP address, with optional Cloudflare proxy support
     */
    private function getClientIP(Request $request): string
    {
        if (config('app.use_cloudflare_ip') && $request->hasHeader('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }
        
        return $request->ip();
    }
}