<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The portal's look: names, logo, colours and font.
 * Uses what the super admin saved on the Branding page, otherwise config/portal.php.
 */
class Branding
{
    public static function portalName(): string
    {
        return Setting::get('portal_name') ?: config('portal.name');
    }

    public static function institution(): string
    {
        return Setting::get('institution_name') ?: config('portal.institution');
    }

    public static function currency(): string
    {
        return config('portal.currency');
    }

    /** Public URL of the uploaded logo, or null if there isn't one. */
    public static function logoUrl(): ?string
    {
        $path = Setting::get('logo_path');

        return $path && file_exists(public_path($path)) ? asset($path) : null;
    }

    /** Two letters for the round badge when there's no logo, e.g. "Student Portal" => "SP". */
    public static function initials(): string
    {
        $words = preg_split('/\s+/', trim(self::portalName()));

        return strtoupper(mb_substr($words[0] ?? 'S', 0, 1).mb_substr($words[1] ?? ($words[0] ?? 'P'), 0, 1));
    }

    public static function color(string $name): string
    {
        return Setting::get("color_{$name}") ?: config("portal.colors.{$name}");
    }

    public static function font(): string
    {
        $font = Setting::get('font') ?: config('portal.font');

        return array_key_exists($font, config('portal.fonts')) ? $font : config('portal.font');
    }

    /** Google Fonts stylesheet URL for the chosen font. */
    public static function fontUrl(): string
    {
        $family = config('portal.fonts')[self::font()];

        return "https://fonts.googleapis.com/css2?family={$family}:wght@400;600;700&display=swap";
    }

    /**
     * CSS variables that override the defaults at the top of public/css/app.css.
     */
    public static function cssVariables(): string
    {
        $sidebar = self::color('sidebar');
        $primary = self::color('primary');
        $accent = self::color('accent');

        $vars = [
            '--ink'          => $sidebar,
            '--ink-soft'     => Color::tint($sidebar, 0.1),
            '--primary'      => $primary,
            '--primary-dark' => Color::shade($primary, 0.2),
            '--bar'          => Color::tint($primary, 0.18),
            '--brass'        => $accent,
            '--brass-soft'   => Color::tint($accent, 0.82),
            '--font'         => '"'.self::font().'", "Segoe UI", system-ui, -apple-system, sans-serif',
        ];

        return collect($vars)->map(fn ($value, $name) => "{$name}: {$value};")->implode(' ');
    }

    /**
     * Money with the currency, e.g. "RM 1,250.00".
     */
    public static function money(float|string|null $amount): string
    {
        return self::currency().' '.number_format((float) $amount, 2);
    }
}
