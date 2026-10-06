<?php

namespace App\Support;

/**
 * Small helpers for the colours the super admin picks on the Branding page.
 */
class Color
{
    /**
     * Mix a colour with black (amount 0 to 1). shade('#1d5c50', 0.2) = 20% darker.
     */
    public static function shade(string $hex, float $amount): string
    {
        return self::mix($hex, [0, 0, 0], $amount);
    }

    /**
     * Mix a colour with white (amount 0 to 1). tint('#1d5c50', 0.9) = very light version.
     */
    public static function tint(string $hex, float $amount): string
    {
        return self::mix($hex, [255, 255, 255], $amount);
    }

    /**
     * How readable white text is on this colour (1 = unreadable, 21 = black).
     * 4.5 or more is needed for normal text (WCAG AA).
     */
    public static function contrastWithWhite(string $hex): float
    {
        return round(1.05 / (self::luminance($hex) + 0.05), 2);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (int $value) {
            $c = $value / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private static function mix(string $hex, array $with, float $amount): string
    {
        $rgb = self::rgb($hex);
        $mixed = array_map(fn ($c, $w) => (int) round($c + ($w - $c) * $amount), $rgb, $with);

        return sprintf('#%02x%02x%02x', ...$mixed);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
