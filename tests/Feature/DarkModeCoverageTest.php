<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DarkModeCoverageTest extends TestCase
{
    public function test_every_interactive_dashboard_uses_the_theme_enabled_app_layout(): void
    {
        $dashboards = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getFilename() === 'dashboard.blade.php');

        $this->assertGreaterThanOrEqual(10, $dashboards->count());

        $dashboards->each(function ($dashboard): void {
            $this->assertStringContainsString(
                '<x-app-layout>',
                File::get($dashboard->getPathname()),
                $dashboard->getPathname().' must use the shared theme-enabled layout.',
            );
        });
    }

    public function test_shared_theme_foundation_covers_legacy_blade_surfaces(): void
    {
        $appCss = File::get(resource_path('css/app.css'));
        $appJs = File::get(resource_path('js/app.js'));
        $navigation = File::get(resource_path('views/layouts/navigation.blade.php'));

        $this->assertStringContainsString('Application-wide dark theme compatibility layer', $appCss);
        $this->assertStringContainsString('[class~="bg-white"]', $appCss);
        $this->assertStringContainsString('input:not([type="checkbox"])', $appCss);
        $this->assertStringContainsString('kweneng-theme', $appJs);
        $this->assertStringContainsString('<x-theme-toggle />', $navigation);
        $this->assertStringContainsString('xl:hidden', $navigation);
    }

    public function test_landing_page_hero_has_a_dedicated_readable_dark_palette(): void
    {
        $home = File::get(resource_path('views/home.blade.php'));
        $this->assertStringContainsString('html.dark .hero-shell', $home);
        $this->assertStringContainsString('linear-gradient(135deg, #0b1117', $home);
        $this->assertStringContainsString('html.dark .hero-school-name', $home);
        $this->assertStringContainsString('html.dark .hero-tagline', $home);
        $this->assertStringContainsString('html.dark .hero-text', $home);
        $this->assertStringContainsString('html.dark .hero-feature', $home);
        $this->assertStringContainsString('html.dark .btn-outline', $home);
        $this->assertStringContainsString('html.dark .home-light-section', $home);
        $this->assertStringContainsString('html.dark .home-section-intro .section-heading', $home);
        $this->assertStringContainsString('html.dark .home-section-intro .section-subtext', $home);
        $this->assertStringContainsString('html.dark .feature-card', $home);
        $this->assertStringContainsString('html.dark .transport-pill', $home);
        $this->assertSame(2, substr_count($home, 'class="home-section-intro text-center'));
    }
}
