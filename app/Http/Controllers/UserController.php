<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Staff accounts. Only admins can reach these pages (see routes/web.php).
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', ['user' => new User(['role' => 'staff'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'role'     => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);   // the password is hashed automatically (see User model casts)

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->name} created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role'     => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // You can't remove your own admin role, or nobody could manage accounts.
        if ($user->is($request->user())) {
            $data['role'] = 'admin';
        }

        // Leave the password unchanged when the field is empty.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->name} updated.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't delete your own account.");
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->name} deleted.");
    }
}
