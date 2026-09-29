<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePasswordDefaults();
        $this->configureModelSafety();

        // Force HTTPS-scheme URL generation (asset(), route(), etc.) once
        // deployed, so generated links never silently downgrade to http://
        // even if the app sits behind a TLS-terminating proxy.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        // Generic ceiling applied to every web request (see bootstrap/app.php).
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Deliberately strict and keyed by email+IP together: an attacker
        // spraying passwords across many accounts from one IP, or many
        // guesses against one account from rotating IPs, both get
        // throttled quickly. This is the same mechanism Laravel Breeze
        // uses under the hood for login brute-force protection.
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(strtolower((string) $request->input('email'))).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinutes(15, 20)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Applies everywhere a new/changed password is validated (register,
     * reset password, update password from profile) since they all share
     * this one `Password::defaults()` rule via `'password' => ['required',
     * Rules\Password::defaults()]` in their respective form requests.
     */
    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers()->uncompromised();

            return $this->app->environment('testing') ? Password::min(6) : $rule;
        });
    }

    private function configureModelSafety(): void
    {
        // Fail loudly in local/testing if code ever touches a relationship
        // or attribute it shouldn't (lazy-loading N+1s, unfilled
        // attributes) instead of failing silently in a way that only shows
        // up as a subtle bug or performance issue in production. Mass
        // assignment protection itself needs no extra call here — Eloquent
        // models are guarded by $fillable by default, and none of the
        // models in this app ever set `$guarded = []`.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
