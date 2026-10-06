{{-- The uploaded logo, or a round badge with the portal's initials. --}}
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="" class="brand-logo">
@else
    <span class="brand-seal" aria-hidden="true">{{ \App\Support\Branding::initials() }}</span>
@endif
