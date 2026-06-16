<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'username'  => ['required', 'string', 'max:255', 'unique:users,username'],
        'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        'password'  => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'full_name'       => $request->full_name,
        'username'        => $request->username,
        'email'           => $request->email,
        'hashed_password' => Hash::make($request->password),
        'is_active'       => false,
        'is_super_admin'  => false,
    ]);

    // Notify admin
    \Illuminate\Support\Facades\Mail::raw(
        "New user registration pending approval:\n\nName: {$user->full_name}\nUsername: {$user->username}\nEmail: {$user->email}\n\nLog in as admin to approve or reject.",
        function ($message) {
            $message->to('akif@bluearrow.ae')
                    ->subject('Blue Arrow Books - New User Registration Pending Approval');
        }
    );

    return redirect('/pending-approval');
}
}
