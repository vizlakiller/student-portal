{{-- Lecturer checkboxes for a session: only the subject's lecturers. Needs $subject, $selected (ids). --}}
<fieldset class="form-section">
    <legend>Lecturers</legend>
    @error('lecturer_ids')<p class="field-error">{{ $message }}</p>@enderror
    @error('lecturer_ids.*')<p class="field-error">{{ $message }}</p>@enderror
    @if ($subject->lecturers->isEmpty())
        <p class="muted">{{ $subject->code }} has no lecturers yet. Assign them on the Subjects page first.</p>
    @else
        <div class="checkbox-list">
            @foreach ($subject->lecturers as $lecturer)
                <label class="checkbox-row">
                    <input type="checkbox" name="lecturer_ids[]" value="{{ $lecturer->id }}" @checked(in_array($lecturer->id, $selected))>
                    <span>{{ $lecturer->name }}</span>
                </label>
            @endforeach
        </div>
        <p class="hint">The session's lecturers enter its students' marks and approve mark changes for them.</p>
    @endif
</fieldset>
