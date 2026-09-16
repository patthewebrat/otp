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

        // The Vite dev server serves assets from another origin during `composer dev`.
        $viteOrigins = $this->viteDevOrigins();
        $viteHttp = $viteOrigins ? ' ' . $viteOrigins['http'] : '';
        $viteConnect = $viteOrigins ? ' ' . $viteOrigins['http'] . ' ' . $viteOrigins['ws'] : '';

        $csp = implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self'{$viteHttp}",
            "style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'{$viteHttp}",
            "font-src 'self' https://cdnjs.cloudflare.com data:{$viteHttp}",
            "img-src 'self' data: blob:",
            "connect-src 'self'" . ($s3Origin ? " {$s3Origin}" : '') . $viteConnect,
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

    /**
     * The Vite dev server origins, or an empty array when Vite is not in dev mode.
     *
     * The hot file exists only while `composer dev` runs, and never in production.
     *
     * @return array{http: string, ws: string}|array{}
     */
    private function viteDevOrigins(): array
    {
        $hotFile = public_path('hot');

        if (!app()->environment('local') || !is_file($hotFile)) {
            return [];
        }

        $origin = rtrim(trim((string) file_get_contents($hotFile)), '/');

        if (!preg_match('#^https?://[^\s/]+$#', $origin)) {
            return [];
        }

        return [
            'http' => $origin,
            'ws' => str_replace(['https://', 'http://'], ['wss://', 'ws://'], $origin),
        ];
    }
}
