{{-- Shared by students/create and students/edit. Needs $student and $programmes. --}}
@csrf

<fieldset class="form-section">
    <legend>Personal details</legend>
    <div class="form-grid">
        <x-input label="Full name" name="name" :value="$student->name" required class="span-2" />
        <x-select label="Gender" name="gender" placeholder="Choose gender" required
                  :options="array_combine(\App\Models\Student::GENDERS, \App\Models\Student::GENDERS)"
                  :value="$student->gender" />
        <x-input label="Date of birth" name="date_of_birth" type="date"
                 :value="$student->date_of_birth?->format('Y-m-d')" />
        <x-input label="Email" name="email" type="email" :value="$student->email" required />
        <x-input label="Phone" name="phone" :value="$student->phone" placeholder="012-3456789" />
    </div>
    <x-textarea label="Address" name="address" :value="$student->address" />
</fieldset>

<fieldset class="form-section">
    <legend>Enrolment</legend>
    <div class="form-grid">
        <x-input label="Student number" name="student_no" :value="$student->student_no" required
                 placeholder="DCS2026001" hint="Must be unique." />
        <x-select label="Programme" name="programme_id" placeholder="Choose programme" required
                  :options="$programmes->mapWithKeys(fn ($p) => [$p->id => $p->code.' – '.$p->name])"
                  :value="$student->programme_id" />
        <x-input label="Intake year" name="intake_year" type="number" min="2000" max="{{ now()->year + 1 }}"
                 :value="$student->intake_year" required />
        <x-input label="Current semester" name="semester" type="number" min="1" max="12"
                 :value="$student->semester" required />
        <x-select label="Status" name="status" required
                  :options="array_combine(\App\Models\Student::STATUSES, \App\Models\Student::STATUSES)"
                  :value="$student->status" />
    </div>
</fieldset>
