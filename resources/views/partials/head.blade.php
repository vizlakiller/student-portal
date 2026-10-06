{{-- Shared <head> contents: title, chosen font, stylesheet and the super admin's brand colours.
     $title must already be HTML-escaped (Blade sections are; plain strings need e()). --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{!! $title !!} | {{ \App\Support\Branding::portalName() }}</title>
@if (\App\Support\Branding::logoUrl())
    <link rel="icon" href="{{ \App\Support\Branding::logoUrl() }}">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="{{ \App\Support\Branding::fontUrl() }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
{{-- Raw output is safe here: colours are checked hex codes and the font comes from a fixed list. --}}
<style>:root { {!! \App\Support\Branding::cssVariables() !!} }</style>
