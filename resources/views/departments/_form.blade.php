{{-- Shared by departments/create and departments/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Code" name="code" :value="$department->code" required placeholder="DCOMP" />
    <x-select label="Head of department" name="hod_id" placeholder="Nobody yet" :options="$hods" :value="$department->hod_id"
              hint="One person can head several departments." />
</div>
<x-input label="Name" name="name" :value="$department->name" required placeholder="Department of Computing" />
@if (empty($hods))
    <p class="muted small">No one has the Head of department role yet. Give it to a staff account under User accounts.</p>
@endif
