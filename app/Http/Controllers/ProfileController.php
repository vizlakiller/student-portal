<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The logged-in user's own details and password.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Staff can change their own name, email and phone. A student's details
     * come from their student record, which only the registrar changes.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasRole('student'), 403);

        $user->update($request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]));

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $request->user()->update([
            'password'             => $data['password'],
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed.');
    }
}
