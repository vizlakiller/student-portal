{{-- Shared by programmes/create and programmes/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Code" name="code" :value="$programme->code" required placeholder="DCS" hint="A short unique code." />
    <x-select label="Level" name="level" placeholder="Choose level" required
              :options="array_combine(\App\Models\Programme::LEVELS, \App\Models\Programme::LEVELS)"
              :value="$programme->level" />
</div>
<x-input label="Name" name="name" :value="$programme->name" required placeholder="Diploma in Computer Science" />
