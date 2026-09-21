<?php

namespace Tests\Feature\Layout;

use Tests\TestCase;

/**
 * Guards the portal layout fixes: banner offset, sidebar logo links, chatbot
 * clearance, and a real action on the profile update forms.
 */
class PortalLayoutRegressionTest extends TestCase
{
    private const BANNER_LAYOUTS = [
        'resources/views/layouts/owner/app.blade.php',
        'resources/views/layouts/studio-photographer/app.blade.php',
        'resources/views/layouts/studio-hr/app.blade.php',
        'resources/views/layouts/studio-finance/app.blade.php',
    ];

    private const OWNED_SIDEBARS = [
        'resources/views/layouts/owner/sidebar.blade.php',
        'resources/views/layouts/studio-photographer/sidebar.blade.php',
        'resources/views/layouts/studio-finance/sidebar.blade.php',
        'resources/views/layouts/admin/sidebar.blade.php',
        'resources/views/layouts/freelancer/sidebar.blade.php',
        'resources/views/layouts/client/sidebar.blade.php',
    ];

    private const PROFILE_VIEWS = [
        'resources/views/admin/view-user-profile.blade.php',
        'resources/views/client/view-user-profile.blade.php',
        'resources/views/freelancer/view-user-profile.blade.php',
        'resources/views/owner/view-user-profile.blade.php',
        'resources/views/studio-hr/view-user-profile.blade.php',
        'resources/views/studio-finance/view-user-profile.blade.php',
        'resources/views/studio-photographer/view-user-profile.blade.php',
    ];

    public function test_subscription_banner_sits_inside_a_content_page_offset(): void
    {
        foreach (self::BANNER_LAYOUTS as $path) {
            $contents = $this->readView($path);

            $this->assertMatchesRegularExpression(
                '/<div class="content-page"[^>]*>\s*@include\(\'partials\.subscription-access-banner\'\)/',
                $contents,
                "The subscription banner is not wrapped in a .content-page offset in {$path}."
            );
        }
    }

    public function test_owned_sidebars_link_the_logo_to_the_site_root(): void
    {
        foreach (self::OWNED_SIDEBARS as $path) {
            $contents = $this->readView($path);

            $this->assertStringNotContainsString(
                'index.html',
                $contents,
                "{$path} still points its logo at index.html."
            );
            $this->assertStringContainsString(
                "url('/')",
                $contents,
                "{$path} does not point its logo at the site root."
            );
        }
    }

    public function test_profile_forms_post_to_the_update_route_with_files_enabled(): void
    {
        foreach (self::PROFILE_VIEWS as $path) {
            $contents = $this->readView($path);

            $this->assertMatchesRegularExpression(
                '/action="\{\{ route\(\'profile\.update\'\) \}\}"\s+method="POST"/',
                $contents,
                "{$path} is missing the profile.update action or POST method."
            );
            $this->assertStringContainsString(
                'enctype="multipart/form-data"',
                $contents,
                "{$path} is missing the multipart enctype for photo uploads."
            );
        }
    }

    public function test_chatbot_launcher_reserves_bottom_space_for_footer_actions(): void
    {
        $contents = $this->readView('resources/views/partials/chatbot-widget.blade.php');

        $this->assertStringContainsString('.content-page', $contents);
        $this->assertStringContainsString('padding-bottom: 6rem;', $contents);
    }

    private function readView(string $relativePath): string
    {
        $path = base_path($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
