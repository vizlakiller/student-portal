{{-- Shared by subjects/create and subjects/edit. --}}
@csrf
<div class="form-grid">
    <x-input label="Code" name="code" :value="$subject->code" required placeholder="CSC1013" />
    <x-input label="Credit hours" name="credit_hours" type="number" min="1" max="6"
             :value="$subject->credit_hours" required />
</div>
<x-input label="Name" name="name" :value="$subject->name" required placeholder="Programming Fundamentals" />
