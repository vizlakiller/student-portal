@extends('layouts.app')
@section('title', 'Bill a programme')
@section('subtitle', 'Add the same charge to every active student in a programme at once.')
@section('back')<a href="{{ route('finance.index') }}">Fees and payments</a>@endsection

@section('content')
    <form method="POST" action="{{ route('finance.billing.store') }}" class="panel form narrow">
        @csrf

        <div class="form-grid">
            <x-select label="Programme" name="programme_id" placeholder="All programmes"
                      :options="$programmes->mapWithKeys(fn ($p) => [$p->id => $p->code.' ('.$p->students_count.' students)'])" />
            <x-input label="Only students now in semester" name="semester" type="number" min="1" max="12"
                     hint="Leave empty to bill every semester." />
        </div>
        <x-input label="Description" name="description" required placeholder="e.g. Semester 1 2026/2027 tuition fee"
                 hint="Students who already have a charge with exactly this description are skipped, so it's safe to run again." />
        <div class="form-grid">
            <x-input label="Amount per student ({{ \App\Support\Branding::currency() }})" name="amount" type="number" step="0.01" min="0.01" required />
            <x-input label="Due date" name="due_date" type="date" />
        </div>

        <p class="muted small">Only students with the status Active are billed.</p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Add this charge to every matching active student?')">Bill students</button>
            <a href="{{ route('finance.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection
