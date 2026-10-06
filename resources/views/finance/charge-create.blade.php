@extends('layouts.app')
@section('title', 'Add charge')
@section('subtitle', $student->name.' ('.$student->student_no.')')
@section('back')<a href="{{ route('finance.students.show', $student) }}">Back to statement</a>@endsection

@section('content')
    <form method="POST" action="{{ route('finance.charges.store', $student) }}" class="panel form narrow">
        @csrf

        <x-input label="Description" name="description" required placeholder="e.g. Hostel fee, Semester {{ $student->semester }}" />
        <div class="form-grid">
            <x-input label="Amount ({{ \App\Support\Branding::currency() }})" name="amount" type="number" step="0.01" min="0.01" required />
            <x-input label="Semester" name="semester" type="number" min="1" max="12" :value="$student->semester" />
            <x-input label="Due date" name="due_date" type="date" />
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add charge</button>
            <a href="{{ route('finance.students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection
