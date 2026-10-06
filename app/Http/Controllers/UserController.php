<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Super admin: staff accounts and their roles. Student accounts are created
 * automatically when the registrar adds a student.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');

        $users = User::query()
            ->when($role, fn ($query) => $query->where('role', $role), fn ($query) => $query->where('role', '!=', 'student'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $counts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('users.index', compact('users', 'role', 'counts'));
    }

    public function create()
    {
        return view('users.create', ['user' => new User(['role' => 'admin_staff'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'staff_no' => ['nullable', 'string', 'max:20'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', Rule::in(array_keys(User::staffRoles()))],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);   // the password is hashed automatically (see User model casts)

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->name} created as {$user->roleLabel()}.");
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
            'staff_no' => ['nullable', 'string', 'max:20'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', Rule::in(array_keys($user->hasRole('student') ? ['student' => 1] : User::staffRoles()))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // You can't remove your own super admin role, or nobody could manage accounts.
        if ($user->is($request->user())) {
            $data['role'] = 'super_admin';
        }

        // A teacher who stops being a teacher no longer teaches their subjects.
        if ($user->hasRole('teacher') && $data['role'] !== 'teacher') {
            $user->subjects()->update(['teacher_id' => null]);
        }

        // Leave the password unchanged when the field is empty.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index', $user->hasRole('student') ? ['role' => 'student'] : [])
            ->with('success', "Account for {$user->name} updated.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't delete your own account.");
        }

        if ($user->hasRole('student')) {
            return back()->with('error', 'Student logins are removed by deleting the student record.');
        }

        $user->delete();   // their subjects become unassigned

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->name} deleted.");
    }
}
