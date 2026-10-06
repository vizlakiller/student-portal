{{-- A labelled text input with its validation error. Usage:
     <x-input label="Name" name="name" :value="$student->name" required /> --}}
@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

<div {{ $attributes->only('class')->class(['field']) }}>
    <label for="{{ $name }}">
        {{ $label }}
        @unless ($required) <span class="optional">(optional)</span> @endunless
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->except('class') }}
    >

    @if ($hint)
        <small class="hint">{{ $hint }}</small>
    @endif

    @error($name)
        <p class="field-error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
