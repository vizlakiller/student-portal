{{-- Shared by terms/create and terms/edit. --}}
@csrf
<x-input label="Name" name="name" :value="$term->name" required placeholder="September 2026" maxlength="60" />
<div class="form-grid">
    <x-input label="Starts on" name="starts_on" type="date" :value="$term->starts_on?->format('Y-m-d')" />
    <x-input label="Ends on" name="ends_on" type="date" :value="$term->ends_on?->format('Y-m-d')" />
</div>
@unless ($term->exists)
    <label class="checkbox">
        <input type="checkbox" name="is_current" value="1" @checked(old('is_current'))> Make this the current term
    </label>
@endunless
