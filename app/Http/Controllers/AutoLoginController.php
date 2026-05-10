<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoLoginController extends Controller
{
    public function login(Request $request)
    {
        $email     = $request->query('email');
        $timestamp = (int) $request->query('ts');
        $token     = $request->query('token');
        $secret    = config('services.portal.secret');

        if (!$email || !$timestamp || !$token || !$secret) {
            abort(403, 'Invalid auto-login request.');
        }

        if (abs(time() - $timestamp) > 30) {
            abort(403, 'Auto-login token expired. Please try again from the portal.');
        }

        $expected = hash_hmac('sha256', $email . '|' . $timestamp, $secret);

        if (!hash_equals($expected, $token)) {
            abort(403, 'Invalid auto-login token.');
        }

        $user = User::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            abort(403, 'No active account found for this email.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
