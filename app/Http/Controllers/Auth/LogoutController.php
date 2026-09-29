<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Always invalidate the session and rotate the CSRF token on
        // logout — this is what stops a captured/old session cookie (or a
        // CSRF token issued to the now-logged-out user) from being replayed
        // afterwards.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
