<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorAppTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'operator'): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test ' . ucfirst($role),
            'email' => $role . '_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $user->save();

        return $user;
    }

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $this->makeToda()->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    public function test_operator_dashboard_renders_mobile_app_header_and_menu()
    {
        $operator = $this->makeOperator();
        $user = $operator->user;

        $res = $this->actingAs($user)->get('/operator/dashboard');

        $res->assertOk();
        $res->assertSee('class="op-header"', false);
        $res->assertSee('data-op-menu-toggle', false);
        $res->assertSee('data-op-menu', false);
        $res->assertSee('data-op-menu-overlay', false);
        $res->assertSee('Welcome,', false);
        $res->assertSee('Manage your ratings and profile', false);
        $res->assertSee('Avg Rating · No Ratings Yet', false);

        $html = $res->getContent();
        $this->assertSame(1, substr_count($html, 'data-op-menu-toggle'), 'Expected exactly one menu trigger');
        $this->assertSame(0, substr_count($html, 'id="sidebarToggle"'), 'Left hamburger must be removed');
        $this->assertSame(0, substr_count($html, 'tw-sidebar'), 'Admin sidebar must not be rendered');
        $this->assertSame(0, substr_count($html, 'tw-topbar'), 'Admin topbar must not be rendered');

        foreach (['Dashboard', 'My Ratings', 'My Profile', 'Settings', 'Logout'] as $item) {
            $res->assertSee($item, false);
        }
        $res->assertSee('op-menu-item active', false);
        $res->assertSee('op-menu-danger', false);
    }

    public function test_operator_dashboard_quick_actions_show_only_print_qr_and_how_to_use()
    {
        $operator = $this->makeOperator();

        $res = $this->actingAs($operator->user)->get('/operator/dashboard');

        $res->assertOk();
        $html = $res->getContent();
        $this->assertSame(2, substr_count($html, 'op-qa-card'), 'Expected exactly 2 quick-action cards');

        $res->assertSee('Print QR', false);
        $res->assertSee('How to use', false);
        $res->assertSee('data-tw-modal-open="howToUseModal"', false);
        $res->assertSee('api.qrserver.com', false);
        $res->assertSee('op-qa-icon-green', false);
        $res->assertSee('op-qa-icon-blue', false);

        $qaGrid = substr($html, strpos($html, 'op-qa-grid'), strpos($html, 'op-stack') - strpos($html, 'op-qa-grid'));
        $this->assertStringNotContainsString('op-menu-item', $qaGrid, 'Quick actions must not include menu links');
    }

    public function test_operator_dashboard_rating_stats_and_breakdown_empty_state()
    {
        $operator = $this->makeOperator();

        $res = $this->actingAs($operator->user)->get('/operator/dashboard');

        $res->assertOk();
        $res->assertSee('data-live="averageRating"', false);
        $res->assertSee('data-live="ratingCaption"', false);
        $res->assertSee('0.0', false);
        $res->assertSee('No ratings yet. Share your QR code!', false);
        $res->assertSee('Your QR code', false);
        $res->assertSee('Print and display inside your motorcycle', false);
        $res->assertSee('Rating breakdown', false);
    }

    public function test_operator_dashboard_rating_stats_with_ratings()
    {
        $operator = $this->makeOperator();
        $operator->ratings()->create([
            'rating' => 5,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);

        $res = $this->actingAs($operator->user)->get('/operator/dashboard');

        $res->assertOk();
        $res->assertSee('5.0', false);
        $res->assertSee('Avg Rating · 1 Rating', false);
        $res->assertDontSee('No ratings yet. Share your QR code!', false);
    }

    public function test_operator_secondary_pages_render_shared_header()
    {
        $operator = $this->makeOperator();

        $pages = [
            '/operator/ratings' => ['My Ratings', 'All feedback received from passengers', 'op-menu-item active'],
            '/operator/profile' => ['My Profile', 'Your registration details and performance snapshot', ''],
            '/operator/settings' => ['Settings', 'Account and security', ''],
        ];

        foreach ($pages as $path => [$title, $subtitle, $activeMark]) {
            $res = $this->actingAs($operator->user)->get($path);

            $res->assertOk();
            $res->assertSee('class="op-header"', false);
            $res->assertSee('data-op-menu-toggle', false);
            $res->assertSee($title, false);
            $res->assertSee($subtitle, false);
            $res->assertSee('op-menu-divider', false);
            $res->assertSee('op-menu-danger', false);

            if ($activeMark !== '') {
                $res->assertSee($activeMark, false);
            }
        }
    }
}
