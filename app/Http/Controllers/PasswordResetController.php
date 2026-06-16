<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = DB::table('users')
            ->where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if ($user) {
            $token   = Str::random(64);
            $expires = Carbon::now()->addHour();

            DB::table('password_reset_tokens')->insert([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expires,
                'used'       => false,
                'created_at' => now(),
            ]);

            $resetLink = url("/reset-password/{$token}");

            // Send email
            try {
                $smtpUser = config('mail.mailers.smtp.username');
                $smtpPass = config('mail.mailers.smtp.password');
                $fromAddr = config('mail.from.address');
                $fromName = config('mail.from.name');

                $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                    config('mail.mailers.smtp.host'),
                    config('mail.mailers.smtp.port')
                );
                $transport->setUsername($smtpUser);
                $transport->setPassword($smtpPass);

                $mailer = new \Symfony\Component\Mailer\Mailer($transport);
                $email = (new \Symfony\Component\Mime\Email())
                    ->from(new \Symfony\Component\Mime\Address($fromAddr, $fromName))
                    ->to($user->email)
                    ->subject('Password Reset - BAMC Accounting')
                    ->html("
                        <div style='font-family:Arial,sans-serif;max-width:500px;margin:40px auto;color:#1a1a2e'>
                        <h2 style='color:#006400'>🏦 BAMC Accounting</h2>
                        <p>You requested a password reset. Click below to set a new password.</p>
                        <p style='margin:30px 0'>
                            <a href='{$resetLink}' style='background:#006400;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold'>
                                Reset My Password
                            </a>
                        </p>
                        <p style='color:#888;font-size:13px'>This link expires in 1 hour.</p>
                        </div>
                    ");
                $mailer->send($email);
            } catch (\Exception $e) {
                \Log::error('Password reset email failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'If that email is registered, a reset link has been sent.');
    }

    public function showReset($token)
    {
        $record = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return redirect('/forgot-password')->with('error', 'This reset link is invalid or has expired.');
        }

        return view('auth.reset-password', compact('token'));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'password'              => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return back()->with('error', 'Invalid or expired reset link.');
        }

        $hash = '$2y$' . substr(password_hash($request->password, PASSWORD_BCRYPT), 4);

        DB::table('users')
            ->where('id', $record->user_id)
            ->update(['hashed_password' => $hash, 'updated_at' => now()]);

        DB::table('password_reset_tokens')
            ->where('id', $record->id)
            ->update(['used' => true]);

        return redirect('/login')->with('success', 'Password updated successfully. Please log in.');
    }
}