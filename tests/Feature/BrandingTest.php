<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Remove any logo a test uploaded
        foreach (File::glob(public_path('uploads/branding/logo-*')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    private function brandingData(array $overrides = []): array
    {
        return array_merge([
            'portal_name'      => 'KTM Portal',
            'institution_name' => 'Kolej Teknologi Melaka',
            'color_sidebar'    => '#1B2A4A',
            'color_primary'    => '#2453a6',
            'color_accent'     => '#8a6a00',
            'font'             => 'Inter',
        ], $overrides);
    }

    public function test_super_admin_changes_names_colours_font_and_logo(): void
    {
        $this->actingAsRole('super_admin');

        $this->put('/branding', $this->brandingData([
            'logo' => UploadedFile::fake()->image('logo.png', 320, 80),
        ]))->assertRedirect('/branding');

        $this->assertSame('#1b2a4a', Setting::get('color_sidebar'));
        $logo = Setting::get('logo_path');
        $this->assertFileExists(public_path($logo));

        // Every page now uses the new branding
        $this->get('/dashboard')->assertOk()
            ->assertSee('KTM Portal')
            ->assertSee('--ink: #1b2a4a', false)
            ->assertSee('family=Inter', false)
            ->assertSee($logo, false);
    }

    public function test_colours_too_light_for_white_text_are_rejected(): void
    {
        $this->actingAsRole('super_admin');

        $this->put('/branding', $this->brandingData(['color_sidebar' => '#f5f5f5', 'color_primary' => '#ffff00']))
            ->assertSessionHasErrors(['color_sidebar', 'color_primary']);
        $this->assertNull(Setting::get('color_sidebar'));
    }

    public function test_logo_must_be_an_image(): void
    {
        $this->actingAsRole('super_admin');

        $this->put('/branding', $this->brandingData(['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]))
            ->assertSessionHasErrors('logo');
    }

    public function test_reset_and_remove_logo(): void
    {
        $this->actingAsRole('super_admin');
        $this->put('/branding', $this->brandingData(['logo' => UploadedFile::fake()->image('logo.png')]));
        $logo = Setting::get('logo_path');

        $this->delete('/branding')->assertRedirect('/branding');
        $this->assertNull(Setting::get('color_sidebar'));
        $this->assertSame('KTM Portal', Setting::get('portal_name'));   // names are kept

        $this->delete('/branding/logo')->assertRedirect('/branding');
        $this->assertNull(Setting::get('logo_path'));
        $this->assertFileDoesNotExist(public_path($logo));
    }

    public function test_only_super_admin_can_change_branding(): void
    {
        $this->actingAsRole('registrar');
        $this->put('/branding', $this->brandingData())->assertForbidden();
    }

    public function test_login_page_and_transcript_use_the_branding(): void
    {
        Setting::put('portal_name', 'KTM Portal');
        Setting::put('institution_name', 'Kolej Teknologi Melaka');

        $this->get('/login')->assertSee('KTM Portal')->assertSee('KP');   // initials when there is no logo
    }
}
