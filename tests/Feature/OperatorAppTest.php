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
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    public function test_operator_dashboard_shows_franchise_expiry_date()
    {
        $operator = $this->makeOperator();

        \App\Models\FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-5555',
            'issued_at' => '2026-01-01',
            'valid_until' => '2027-01-01',
            'status' => \App\Models\FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($operator->user)->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('FR-5555', false)
            ->assertSee('Valid until', false)
            ->assertSee('Jan 01, 2027', false);
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

    private function makeRating(Operator $operator, ?Toda $toda, int $stars, string $origin): void
    {
        $operator->ratings()->create([
            'toda_id' => $toda?->id,
            'rating' => $stars,
            'reason' => $origin,
            'start_location' => $origin,
            'end_location' => $origin . ' Terminal',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    private function makeMultiTodaOperator(): array
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $operator = $this->makeOperator();
        $operator->forceFill(['toda_id' => $todaA->id])->save();
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);

        return ['operator' => $operator, 'a' => $todaA, 'b' => $todaB];
    }

    public function test_multi_toda_operator_dashboard_shows_switcher_and_overall_average()
    {
        ['operator' => $operator, 'a' => $todaA, 'b' => $todaB] = $this->makeMultiTodaOperator();

        $this->makeRating($operator, $todaA, 5, 'Alpha');
        $this->makeRating($operator, $todaB, 1, 'Gamma');

        // Default view = all TODAs combined -> average of 5 and 1 = 3.0.
        $this->actingAs($operator->user)->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('operatorToda', false)          // the TODA <select> is present
            ->assertSee('All TODAs', false)
            ->assertSee('Overall (All TODAs)', false)
            ->assertSee('3.0', false);
    }

    public function test_operator_dashboard_can_scope_to_a_single_toda()
    {
        ['operator' => $operator, 'a' => $todaA, 'b' => $todaB] = $this->makeMultiTodaOperator();

        $this->makeRating($operator, $todaA, 5, 'Alpha');
        $this->makeRating($operator, $todaB, 1, 'Gamma');

        // Scoped to TODA A -> only the 5-star counts for the headline average.
        $this->actingAs($operator->user)->get('/operator/dashboard?toda=' . $todaA->id)
            ->assertOk()
            ->assertSee('5.0', false)
            // The overall strip still reports every TODA.
            ->assertSee('Overall (All TODAs)', false)
            ->assertSee('3.0', false);
    }

    public function test_operator_ratings_page_filters_by_selected_toda()
    {
        ['operator' => $operator, 'a' => $todaA, 'b' => $todaB] = $this->makeMultiTodaOperator();

        $this->makeRating($operator, $todaA, 5, 'Alpha');
        $this->makeRating($operator, $todaB, 1, 'Gamma');

        $this->actingAs($operator->user)->get('/operator/ratings?toda=' . $todaB->id)
            ->assertOk()
            ->assertSee('Gamma', false)
            ->assertDontSee('Alpha', false);
    }

    public function test_single_toda_operator_has_no_toda_switcher()
    {
        $operator = $this->makeOperator();
        $operator->joinToda($operator->toda_id);
        $this->makeRating($operator, $operator->toda, 4, 'Solo');

        $this->actingAs($operator->user)->get('/operator/dashboard')
            ->assertOk()
            ->assertDontSee('operatorToda', false)
            ->assertDontSee('Overall (All TODAs)', false);
    }
}
