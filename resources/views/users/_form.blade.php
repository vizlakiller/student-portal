{{-- Shared by users/create and users/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Name" name="name" :value="$user->name" required />
    <x-input label="Email" name="email" type="email" :value="$user->email" required />
</div>

@if ($user->exists && $user->is(auth()->user()))
    <p class="muted">You are an admin. You can't change your own role.</p>
    <input type="hidden" name="role" value="admin">
@else
    <x-select label="Role" name="role" required
              :options="['staff' => 'Staff: manage students, programmes, subjects and results', 'admin' => 'Admin: everything staff can do, plus manage accounts']"
              :value="$user->role" />
@endif

<div class="form-grid">
    <x-input :label="$user->exists ? 'New password' : 'Password'" name="password" type="password"
             :required="! $user->exists" autocomplete="new-password"
             :hint="$user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
    <x-input label="Confirm password" name="password_confirmation" type="password"
             :required="! $user->exists" autocomplete="new-password" />
</div>
