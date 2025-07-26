<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Assuming your User model is in App\Models

class PasswordResetController extends Controller
{
    /**
     * Handle the manual password reset request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function manualReset(Request $request)
    {
        // 1. Validate the incoming request data
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.exists' => 'The provided email address is not registered.',
            'password.min' => 'Your new password must be at least 8 characters long.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        // 2. Find the user by email
        $user = User::where('email', $request->email)->first();

        // 3. Update the user's password
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            // 4. Redirect with a success message
            return redirect('/')->with('status', 'Your password has been reset successfully! You can now log in with your new password.');
        }

        // This part should ideally not be reached if 'email.exists' validation works,
        // but it's good for a fallback.
        return back()->withErrors(['email' => 'Unable to reset password for this email.']);
    }
}