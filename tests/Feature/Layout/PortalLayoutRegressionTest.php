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

    public function test_cancellation_recovery_pages_keep_actions_clear_of_the_sidebar(): void
    {
        foreach (['owner', 'client'] as $role) {
            $contents = $this->readView("resources/views/{$role}/cancellation-recovery.blade.php");
            $this->assertMatchesRegularExpression(
                '/@section\(\'content\'\)\s*<div class="content-page">/',
                $contents,
                "The {$role} recovery page must use the portal content offset."
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

    public function test_tutorial_can_be_replayed_from_business_and_client_profile_menus(): void
    {
        foreach (['client', 'owner', 'freelancer'] as $role) {
            $contents = $this->readView("resources/views/layouts/{$role}/topbar.blade.php");
            $this->assertStringContainsString("route('onboarding', ['replay' => 1])", $contents);
            $this->assertStringContainsString('Replay tutorial', $contents);
            $this->assertStringContainsString('dropdown-item studio-profile-link py-2', $contents);
        }
    }

    public function test_long_topbar_names_are_bounded_without_removing_accessible_text(): void
    {
        $contents = $this->readView('resources/views/layouts/partials/portal-base-scripts.blade.php');
        $this->assertStringContainsString('.nav-user h5', $contents);
        $this->assertStringContainsString('max-width: min(16rem, 20vw);', $contents);
        $this->assertStringContainsString('text-overflow: ellipsis;', $contents);
        foreach (['admin', 'owner', 'client', 'freelancer', 'studio-hr', 'studio-finance', 'studio-photographer'] as $role) {
            $this->assertStringContainsString('layouts.partials.portal-base-scripts', $this->readView("resources/views/layouts/{$role}/app.blade.php"));
            $this->assertStringContainsString('full_name', $this->readView("resources/views/layouts/{$role}/topbar.blade.php"));
        }
    }

    public function test_studio_listing_actions_wrap_and_keep_tap_targets(): void
    {
        $contents = $this->readView('resources/views/client/dashboard.blade.php');
        $this->assertStringContainsString('d-flex flex-wrap justify-content-between align-items-center gap-2', $contents);
        $this->assertStringContainsString('studio-profile-link text-nowrap', $contents);
        $styles = $this->readView('resources/views/layouts/partials/portal-base-scripts.blade.php');
        $this->assertStringContainsString('.studio-profile-link', $styles);
        $this->assertStringContainsString('min-height: 44px;', $styles);
    }

    public function test_new_controls_use_existing_ins_theme_tokens_for_accessible_contrast(): void
    {
        foreach (['resources/views/onboarding.blade.php', 'resources/views/layouts/partials/portal-base-scripts.blade.php'] as $path) {
            $contents = $this->readView($path);
            $this->assertStringContainsString('var(--ins-body-color)', $contents);
            $this->assertStringContainsString('outline: 3px solid var(--ins-body-color) !important;', $contents);
            $this->assertStringNotContainsString('var(--bs-primary)', $contents);
        }
    }

    public function test_tutorial_avoids_the_auth_layouts_fixed_mobile_padding(): void
    {
        $contents = $this->readView('resources/views/onboarding.blade.php');
        $this->assertStringContainsString('.auth-box { padding: 0; }', $contents);
        $this->assertStringContainsString('padding: clamp(1rem, 3vw, 2rem);', $contents);
    }

    private function readView(string $relativePath): string
    {
        $path = base_path($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
