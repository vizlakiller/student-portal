{{-- A labelled dropdown. $options is [value => label]. Usage:
     <x-select label="Status" name="status" :options="$statuses" :value="$student->status" required /> --}}
@props(['label', 'name', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null])

<div {{ $attributes->only('class')->class(['field']) }}>
    <label for="{{ $name }}">
        {{ $label }}
        @unless ($required) <span class="optional">(optional)</span> @endunless
    </label>

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->except('class') }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error($name)
        <p class="field-error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
