@extends('layouts.app')
@section('title', 'Marks: '.$subject->code)
@section('subtitle', $subject->name.', '.$subject->credit_hours.' credit hours')
@section('back')<a href="{{ route('marks.index') }}">My subjects</a>@endsection

@section('content')
    @if ($results->isEmpty())
        <div class="panel empty-state">
            <p>No students are registered for {{ $subject->code }} yet. The registrar registers students for subjects.</p>
        </div>
    @else
        <form method="POST" action="{{ route('marks.update', $subject) }}" class="panel flush">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-error inset">Some marks were not saved. Check the boxes marked in red.</div>
            @endif

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student no.</th>
                            <th>Name</th>
                            <th class="num">Semester</th>
                            <th>Marks (0 to 100)</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $result)
                            @php $field = 'marks.'.$result->id; @endphp
                            <tr>
                                <td class="ref">{{ $result->student->student_no }}</td>
                                <td>
                                    {{ $result->student->name }}
                                    @if ($result->pendingChange)
                                        <div class="pending small">HOD asks to change this to {{ $result->pendingChange->new_marks }}. See Mark changes.</div>
                                    @endif
                                </td>
                                <td class="num">{{ $result->semester }}</td>
                                <td>
                                    <input type="number" min="0" max="100" step="1" class="marks-input"
                                           name="marks[{{ $result->id }}]" value="{{ old($field, $result->marks) }}"
                                           aria-label="Marks for {{ $result->student->name }}"
                                           @error($field) aria-invalid="true" @enderror>
                                    @error($field)<p class="field-error">{{ $message }}</p>@enderror
                                </td>
                                <td>
                                    @if ($result->isMarked())
                                        @include('partials.grade', ['grade' => $result->grade])
                                    @else
                                        <span class="pending">Not marked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-actions inset">
                <button type="submit" class="btn btn-primary">Save all marks</button>
                <span class="muted small">Leave a box empty if that student has no marks yet.</span>
            </div>
        </form>
    @endif

    @include('results._scale')
@endsection
