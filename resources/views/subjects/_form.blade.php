{{-- Shared by subjects/create and subjects/edit. Needs $subject, $departments, $lecturers. --}}
@csrf
<div class="form-grid">
    <x-input label="Code" name="code" :value="$subject->code" required placeholder="CSC1013" />
    <x-input label="Credit hours" name="credit_hours" type="number" min="1" max="6"
             :value="$subject->credit_hours" required />
</div>
<x-input label="Name" name="name" :value="$subject->name" required placeholder="Programming Fundamentals" />
<x-select label="Department" name="department_id" placeholder="Choose department" :options="$departments"
          :value="$subject->department_id" :required="! auth()->user()->isSuperAdmin()"
          :hint="auth()->user()->isSuperAdmin() ? null : 'Only the departments you head are listed.'" />

@can('assign-lecturers')
    @php $selected = old('lecturer_ids', $subject->exists ? $subject->lecturers->pluck('id')->all() : []); @endphp
    <fieldset class="form-section">
        <legend>Lecturers</legend>
        @error('lecturer_ids.*')<p class="field-error">{{ $message }}</p>@enderror
        @if (empty($lecturers))
            <p class="muted small">There are no lecturer accounts yet. The super admin adds them under User accounts.</p>
        @else
            <div class="checkbox-list two-columns">
                @foreach ($lecturers as $id => $name)
                    <label class="checkbox-row">
                        <input type="checkbox" name="lecturer_ids[]" value="{{ $id }}" @checked(in_array($id, $selected))>
                        <span>{{ $name }}</span>
                    </label>
                @endforeach
            </div>
            <p class="hint">Tick everyone who teaches this subject. You choose which of them teach each session on the Sessions page.</p>
        @endif
    </fieldset>
@endcan
