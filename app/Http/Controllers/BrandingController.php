<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Branding;
use App\Support\Color;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

/**
 * Super admin: portal name, institution name, logo, colours and font.
 */
class BrandingController extends Controller
{
    private const COLOR_KEYS = ['color_sidebar', 'color_primary', 'color_accent'];

    public function edit()
    {
        return view('branding.edit', [
            'portalName'  => Branding::portalName(),
            'institution' => Branding::institution(),
            'logoUrl'     => Branding::logoUrl(),
            'colors'      => [
                'sidebar' => Branding::color('sidebar'),
                'primary' => Branding::color('primary'),
                'accent'  => Branding::color('accent'),
            ],
            'font'        => Branding::font(),
            'fonts'       => array_keys(config('portal.fonts')),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'portal_name'      => ['required', 'string', 'max:60'],
            'institution_name' => ['required', 'string', 'max:120'],
            'color_sidebar'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/', $this->readableWithWhite(4.5)],
            'color_primary'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/', $this->readableWithWhite(4.5)],
            'color_accent'     => ['required', 'regex:/^#[0-9a-fA-F]{6}$/', $this->readableWithWhite(3)],
            'font'             => ['required', Rule::in(array_keys(config('portal.fonts')))],
            'logo'             => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],   // max 1 MB
        ], [
            'logo.max' => 'The logo must be smaller than 1 MB.',
        ], [
            'color_sidebar' => 'sidebar colour',
            'color_primary' => 'button colour',
            'color_accent'  => 'accent colour',
        ]);

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile();

            $file = $request->file('logo');
            $name = 'logo-'.now()->format('YmdHis').'.'.$file->extension();
            $file->move(public_path('uploads/branding'), $name);

            Setting::put('logo_path', 'uploads/branding/'.$name);
        }

        foreach (['portal_name', 'institution_name', 'font'] as $key) {
            Setting::put($key, $data[$key]);
        }

        foreach (self::COLOR_KEYS as $key) {
            Setting::put($key, strtolower($data[$key]));
        }

        return redirect()->route('branding.edit')->with('success', 'Branding saved. Every page now uses it.');
    }

    public function removeLogo()
    {
        $this->deleteLogoFile();
        Setting::forgetMany(['logo_path']);

        return redirect()->route('branding.edit')->with('success', 'Logo removed.');
    }

    /**
     * Put colours and font back to the defaults in config/portal.php.
     * Names and logo are kept.
     */
    public function reset()
    {
        Setting::forgetMany([...self::COLOR_KEYS, 'font']);

        return redirect()->route('branding.edit')->with('success', 'Colours and font reset to the defaults.');
    }

    private function deleteLogoFile(): void
    {
        $path = Setting::get('logo_path');

        if ($path && str_starts_with($path, 'uploads/branding/')) {
            File::delete(public_path($path));
        }
    }

    /**
     * White text must stay readable on the chosen colour.
     */
    private function readableWithWhite(float $minimum): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($minimum) {
            if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) && Color::contrastWithWhite($value) < $minimum) {
                $fail('The :attribute is too light: white text on it would be hard to read. Choose a darker colour.');
            }
        };
    }
}
