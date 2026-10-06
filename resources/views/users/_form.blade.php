{{-- Shared by users/create and users/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Name" name="name" :value="$user->name" required />
    <x-input label="Email" name="email" type="email" :value="$user->email" required hint="Used to log in." />
    <x-input label="Staff number" name="staff_no" :value="$user->staff_no" />
    <x-input label="Phone" name="phone" :value="$user->phone" />
</div>

@if ($user->exists && $user->is(auth()->user()))
    <p class="muted">You are the super admin. You can't change your own role.</p>
    <input type="hidden" name="role" value="super_admin">
@elseif ($user->hasRole('student'))
    <p class="muted">This is a student login. Its role can't be changed.</p>
    <input type="hidden" name="role" value="student">
@else
    <x-select label="Role" name="role" required :options="\App\Models\User::staffRoles()" :value="$user->role"
              hint="See 'What each role can do' on the User accounts page." />
@endif

<div class="form-grid">
    <x-input :label="$user->exists ? 'New password' : 'Password'" name="password" type="password"
             :required="! $user->exists" autocomplete="new-password"
             :hint="$user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
    <x-input label="Confirm password" name="password_confirmation" type="password"
             :required="! $user->exists" autocomplete="new-password" />
</div>
