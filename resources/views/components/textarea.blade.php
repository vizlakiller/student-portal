{{-- A labelled multi-line text box. Usage:
     <x-textarea label="Address" name="address" :value="$student->address" /> --}}
@props(['label', 'name', 'value' => null, 'required' => false, 'rows' => 3])

<div {{ $attributes->only('class')->class(['field']) }}>
    <label for="{{ $name }}">
        {{ $label }}
        @unless ($required) <span class="optional">(optional)</span> @endunless
    </label>

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->except('class') }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="field-error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
