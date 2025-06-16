<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\GetsClientIP;

class CheckFileUploadIP
{
    use GetsClientIP;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $whitelistConfig = config('app.file_upload_whitelist');
        
        // If no whitelist is configured, allow all IPs
        if (empty($whitelistConfig)) {
            return $next($request);
        }
        
        // Parse the whitelist
        $whitelist = array_map('trim', explode(',', $whitelistConfig));
        $clientIP = $this->getClientIP($request);
        
        // Check if client IP is in whitelist
        if (!in_array($clientIP, $whitelist)) {
            return response()->json([
                'error' => 'File upload is not available from your location.'
            ], 403);
        }
        
        return $next($request);
    }
}