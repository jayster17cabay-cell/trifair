<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TODA membership visibility on the dashboards and the Reports page:
 * pending applications tally on the dashboard KPI, and a driver who joined
 * a TODA (home TODA aside) appears under it with a per-TODA breakdown.
 */
class DashboardMembershipTallyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->forceFill([
            'name' => ucfirst(str_replace('_', ' ', $role)) . ' ' . Str::random(4),
            'email' => $role . '_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $user->save();

        return $user;
    }

    private function makeToda(string $name): Toda
    {
        return Toda::create([
            'name' => $name,
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(Toda $toda): Operator
    {
        return Operator::create([
            'user_id' => $this->makeUser('operator')->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makeRating(Operator $operator, int $stars, ?int $todaId): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'toda_id' => $todaId,
            'rating' => $stars,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    public function test_dashboards_tally_pending_toda_memberships_for_the_right_stage()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');

        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $todaC = $this->makeToda('Gamma TODA');

        $this->makeOperator($todaA)->joinToda($todaA->id, OperatorToda::STATUS_PENDING_TFRB);
        $this->makeOperator($todaA)->joinToda($todaB->id, OperatorToda::STATUS_PENDING_SUPERADMIN);
        $this->makeOperator($todaA)->joinToda($todaC->id, OperatorToda::STATUS_PENDING_ADMIN);

        app(AdminDashboardService::class)->flush();

        // Superadmin only acts on the final (superadmin) stage.
        $this->actingAs($admin)->getJson('/superadmin/dashboard')
            ->assertOk()
            ->assertJsonPath('pendingTodaMemberships', 1);

        // TFRB verifies president-approved requests (legacy pending_admin included).
        $this->actingAs($officer)->getJson('/tfrb-officer/dashboard')
            ->assertOk()
            ->assertJsonPath('pendingTodaMemberships', 2);

        // The KPI card renders on both dashboards.
        $this->actingAs($admin)->get('/superadmin/dashboard')->assertOk()->assertSee('Pending TODA', false);
        $this->actingAs($officer)->get('/tfrb-officer/dashboard')->assertOk()->assertSee('Pending TODA', false);
    }

    public function test_reports_list_operator_under_joined_toda_with_per_toda_breakdown()
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');

        // Home TODA is Alpha; the driver then joined Beta.
        $operator = $this->makeOperator($todaA);
        $operator->user->forceFill(['name' => 'Multi Toda Driver'])->save();
        $operator->joinToda($todaB->id);

        $this->makeRating($operator, 4, $todaA->id);
        $this->makeRating($operator, 2, $todaA->id);
        $this->makeRating($operator, 5, $todaB->id);

        // Filtering by the JOINED TODA (Beta) must still list the driver, whose
        // home TODA is Alpha — this is the "wala sa may toda kung saan sya
        // sumali" bug.
        $res = $this->actingAs($admin)->get('/superadmin/reports?toda_id=' . $todaB->id);

        $res->assertOk();
        $res->assertSee('Multi Toda Driver', false);
        // Both TODAs and their own averages appear in the breakdown chips.
        $res->assertSee('Alpha TODA', false);
        $res->assertSee('Beta TODA', false);
        $res->assertSee('5.0', false); // Beta: 5
        $res->assertSee('3.0', false); // Alpha: (4 + 2) / 2
    }
}
