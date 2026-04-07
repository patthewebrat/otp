<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $s3Bucket = config('filesystems.disks.s3.bucket');
        $s3Region = config('filesystems.disks.s3.region');
        $s3Origin = $s3Bucket && $s3Region
            ? "https://{$s3Bucket}.s3.{$s3Region}.amazonaws.com"
            : '';

        $csp = implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'",
            "font-src 'self' https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'" . ($s3Origin ? " {$s3Origin}" : ''),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "frame-src 'none'",
            "worker-src 'self' blob:",
            'upgrade-insecure-requests',
        ]));

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
