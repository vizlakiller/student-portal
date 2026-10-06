@extends('layouts.app')
@section('title', 'Branding')
@section('subtitle', 'How the portal looks for everyone. Changes apply to every page, transcript and receipt.')

@section('content')
    {{-- All font choices, so the dropdown and preview can show them --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ collect(config('portal.fonts'))->map(fn ($f) => 'family='.$f.':wght@400;700')->implode('&') }}&display=swap">

    <div class="branding-layout">
        <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="panel form" id="branding-form">
            @csrf
            @method('PUT')

            <fieldset class="form-section">
                <legend>Names</legend>
                <x-input label="Portal name" name="portal_name" :value="$portalName" required maxlength="60"
                         hint="Shown in the sidebar, on the login page and in browser tabs." />
                <x-input label="Institution name" name="institution_name" :value="$institution" required maxlength="120"
                         hint="Printed at the top of transcripts and receipts." />
            </fieldset>

            <fieldset class="form-section">
                <legend>Logo</legend>
                <div class="logo-row">
                    <div class="logo-current">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Current logo" id="logo-current">
                        @else
                            <span class="muted" id="logo-current">No logo yet. The portal's initials are shown instead.</span>
                        @endif
                    </div>
                    <x-input label="Upload a new logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                             hint="PNG, JPG or WebP, up to 1 MB. A wide logo about 160 × 40 pixels fits the sidebar best." />
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Colours</legend>
                <div class="color-grid">
                    @foreach ([
                        'sidebar' => ['Sidebar and headings', 4.5],
                        'primary' => ['Buttons and links', 4.5],
                        'accent'  => ['Accent: logo ring, CGPA seal, focus outline', 3],
                    ] as $key => [$label, $minimum])
                        <div class="field">
                            <label for="color_{{ $key }}">{{ $label }}</label>
                            <div class="color-input">
                                <input type="color" id="color_{{ $key }}" name="color_{{ $key }}"
                                       value="{{ old('color_'.$key, $colors[$key]) }}" data-minimum="{{ $minimum }}"
                                       @error('color_'.$key) aria-invalid="true" @enderror>
                                <span class="color-hex">{{ old('color_'.$key, $colors[$key]) }}</span>
                            </div>
                            <p class="field-error contrast-warning" hidden>Too light: white text on it would be hard to read.</p>
                            @error('color_'.$key)
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Font</legend>
                <div class="field">
                    <label for="font">Font for all pages</label>
                    <select id="font" name="font">
                        @foreach ($fonts as $option)
                            <option value="{{ $option }}" style="font-family: '{{ $option }}'" @selected(old('font', $font) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save branding</button>
            </div>
        </form>

        <aside class="branding-aside">
            <p class="preview-label">Preview</p>
            <div class="preview" id="preview" style="--ink: {{ $colors['sidebar'] }}; --primary: {{ $colors['primary'] }}; --brass: {{ $colors['accent'] }}; font-family: '{{ $font }}', sans-serif;">
                <div class="preview-sidebar">
                    <div @class(['preview-brand', 'has-logo' => $logoUrl])>
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="brand-logo" id="preview-logo">
                        @else
                            <span class="brand-seal" id="preview-seal">{{ \App\Support\Branding::initials() }}</span>
                        @endif
                        <span id="preview-name">{{ $portalName }}</span>
                    </div>
                    <span class="preview-nav active">Dashboard</span>
                    <span class="preview-nav">Students</span>
                    <span class="preview-nav">Subjects</span>
                </div>
                <div class="preview-main">
                    <strong class="preview-heading">Students</strong>
                    <span class="preview-link">Ahmad Faiz bin Hassan</span>
                    <span class="btn btn-primary preview-button">Add student</span>
                    <span class="preview-seal"><b>3.67</b>CGPA</span>
                </div>
            </div>

            <div class="panel branding-extra">
                @if ($logoUrl)
                    <form method="POST" action="{{ route('branding.logo.destroy') }}" onsubmit="return confirm('Remove the logo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-button danger">Remove logo</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('branding.reset') }}" onsubmit="return confirm('Put the colours and font back to the defaults?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="link-button">Reset colours and font to defaults</button>
                </form>
            </div>
        </aside>
    </div>

    <script>
        // Live preview while choosing. Nothing is saved until "Save branding".
        const preview = document.getElementById('preview');
        const cssVar = { color_sidebar: '--ink', color_primary: '--primary', color_accent: '--brass' };

        // Contrast of white text on a colour (same formula the server checks)
        function contrastWithWhite(hex) {
            const lum = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16) / 255)
                .map(c => c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
            return 1.05 / (0.2126 * lum[0] + 0.7152 * lum[1] + 0.0722 * lum[2] + 0.05);
        }

        document.querySelectorAll('input[type=color]').forEach(input => {
            input.addEventListener('input', () => {
                preview.style.setProperty(cssVar[input.name], input.value);
                input.nextElementSibling.textContent = input.value;
                const field = input.closest('.field');
                field.querySelector('.contrast-warning').hidden = contrastWithWhite(input.value) >= Number(input.dataset.minimum);
            });
        });

        document.getElementById('font').addEventListener('change', e => {
            preview.style.fontFamily = `'${e.target.value}', sans-serif`;
        });

        document.getElementById('portal_name').addEventListener('input', e => {
            document.getElementById('preview-name').textContent = e.target.value;
        });

        document.getElementById('logo').addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            const brand = preview.querySelector('.preview-brand');
            let img = document.getElementById('preview-logo');
            if (!img) {
                img = document.createElement('img');
                img.id = 'preview-logo';
                img.className = 'brand-logo';
                img.alt = '';
                document.getElementById('preview-seal')?.remove();
                brand.prepend(img);
                brand.classList.add('has-logo');
            }
            img.src = url;
        });
    </script>
@endsection
