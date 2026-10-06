@extends('layouts.app')
@section('title', 'User accounts')
@section('subtitle', 'Who can log in and what each person can do. Student logins are created when the registrar adds a student.')

@section('actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">Add staff account</a>
@endsection

@section('content')
    <nav class="tabs" aria-label="Filter by role">
        <a href="{{ route('users.index') }}" @class(['active' => ! $role])>All staff</a>
        @foreach (\App\Models\User::roles() as $key => $label)
            <a href="{{ route('users.index', ['role' => $key]) }}" @class(['active' => $role === $key])>
                {{ $label }} <span class="tab-count">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <section class="panel flush">
        @if ($users->isEmpty())
            <div class="empty-state"><p>No accounts with this role yet.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Staff no.</th>
                            <th>Role</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="strong">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user())) <span class="muted small">(you)</span> @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td class="ref">{{ $user->staff_no ?? '–' }}</td>
                                <td><span class="role role-{{ $user->role }}">{{ $user->roleLabel() }}</span></td>
                                <td class="row-actions">
                                    <a href="{{ route('users.edit', $user) }}">Edit</a>
                                    @unless ($user->is(auth()->user()) || $user->hasRole('student'))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              onsubmit="return confirm(@js('Delete the account for '.$user->name.'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button danger">Delete</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{ $users->links() }}

    <section class="panel">
        <h2>What each role can do</h2>
        <dl class="role-guide">
            <div><dt>Super admin</dt><dd>Everything, including branding, user accounts and changing marks directly.</dd></div>
            <div><dt>Admin staff</dt><dd>Views students, teachers and subjects. Edits students' personal and contact details. Can't see results.</dd></div>
            <div><dt>Head of department</dt><dd>Assigns teachers to subjects, sees all results, asks for mark changes (the subject teacher must approve).</dd></div>
            <div><dt>Registrar</dt><dd>Adds and edits students, registers them for subjects, sees results and prints transcripts. Can't change marks.</dd></div>
            <div><dt>Teacher</dt><dd>Enters marks for their own subjects and approves or rejects mark changes.</dd></div>
            <div><dt>Accountant</dt><dd>Bills fees, records payments, prints receipts and sees who owes money.</dd></div>
            <div><dt>Student</dt><dd>Sees their own subjects, results and transcript.</dd></div>
        </dl>
        <p class="muted small">To change what a role can do, edit config/roles.php.</p>
    </section>
@endsection
