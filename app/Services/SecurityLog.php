<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper that writes security-relevant events (failed logins,
 * lockouts, role changes, account deactivation/deletion) to their own
 * `security` log channel — storage/logs/security-YYYY-MM-DD.log, rotated
 * daily and kept for LOG_SECURITY_DAYS (default 90) — instead of mixing
 * them into the general application log where they're easy to miss.
 *
 * Never pass secrets (passwords, tokens) in $context.
 */
class SecurityLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        static::write('info', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        static::write('warning', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        $request = request();

        Log::channel('security')->{$level}($event, $context + [
            'actor_id' => auth()->id(),
            'ip' => $request?->ip(),
            'user_agent' => str($request?->userAgent() ?? '')->limit(120)->toString(),
        ]);
    }
}
