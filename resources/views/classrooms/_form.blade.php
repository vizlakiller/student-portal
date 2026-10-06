{{-- Shared by classrooms/create and classrooms/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Code" name="code" :value="$classroom->code" required placeholder="MK-201" />
    <x-select label="Type" name="type" required :options="array_combine(\App\Models\Classroom::TYPES, \App\Models\Classroom::TYPES)" :value="$classroom->type" />
</div>
<x-input label="Name" name="name" :value="$classroom->name" required placeholder="Computer Lab A" />
<div class="form-grid">
    <x-input label="Building" name="building" :value="$classroom->building" placeholder="Blok Makmal" />
    <x-input label="Seats" name="capacity" type="number" min="1" max="2000" :value="$classroom->capacity" required />
</div>
