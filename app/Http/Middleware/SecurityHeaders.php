<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a baseline set of security response headers to every request.
 *
 * These headers are defense-in-depth: they don't replace input validation,
 * output escaping, or authorization checks, but they meaningfully reduce
 * the blast radius of whole classes of bugs (clickjacking, MIME-sniffing
 * and especially XSS: a strict CSP stops an injected <script> or an
 * exfiltrating request from working even if one ever slipped through).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $fontHost = 'https://fonts.bunny.net';

        // Local `npm run dev` serves assets from the Vite dev server on
        // another port and talks to it over a websocket (HMR); without
        // allowing that origin the page would render completely unstyled.
        // Empty in production, and only when public/hot exists.
        $vite = $this->viteDevOrigins();
        $viteHttp = $vite['http'] ?? '';
        $viteWs = $vite['ws'] ?? '';

        // 'unsafe-eval' and style 'unsafe-inline' are here for one reason:
        // Alpine.js (bundled with Livewire) evaluates directive expressions
        // via `new Function()` and toggles `x-show` through an inline style
        // attribute. This is a known, accepted trade-off for any
        // Livewire/Alpine app; see README.md ("Security") for how to
        // tighten it if you need a stricter policy than this starter ships.
        $directives = [
            "default-src 'self'",
            trim("script-src 'self' 'unsafe-eval' {$viteHttp}"),
            trim("style-src 'self' 'unsafe-inline' {$fontHost} {$viteHttp}"),
            trim("font-src 'self' {$fontHost} {$viteHttp}"),
            trim("img-src 'self' data: blob: {$viteHttp}"),
            trim("connect-src 'self' {$viteHttp} {$viteWs}"),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        // Only meaningful (and only safe) once the site is really served
        // over HTTPS; on plain-http localhost some browsers would upgrade
        // every same-origin request to https:// and break local dev.
        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    /**
     * @return array{http?: string, ws?: string}
     */
    private function viteDevOrigins(): array
    {
        if (! app()->environment('local')) {
            return [];
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return [];
        }

        $parts = parse_url(trim((string) file_get_contents($hotFile)));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return [
            'http' => $parts['scheme'].'://'.$authority,
            'ws' => ($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$authority,
        ];
    }
}
