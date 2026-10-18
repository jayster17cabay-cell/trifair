<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperadminTodaMembersModalTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'superadmin'): User
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

    private function makeOperator(Toda $toda, ?string $name = null): Operator
    {
        $user = $this->makeUser('operator');
        if ($name) {
            $user->forceFill(['name' => $name])->save();
        }
        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makePresident(Toda $toda, ?string $name = null): Operator
    {
        $user = $this->makeUser('operator_president');
        if ($name) {
            $user->forceFill(['name' => $name])->save();
        }
        $user->forceFill(['toda_id' => $toda->id])->save();

        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
        $operator->setTodaPresidency($toda->id, true);

        return $operator;
    }

    public function test_superadmin_dashboard_renders_toda_members_modal()
    {
        $toda = $this->makeToda();
        $this->makeOperator($toda);
        $admin = $this->makeUser('superadmin');

        $html = $this->actingAs($admin)
            ->get('/superadmin/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="todaModal"', $html);
        $this->assertStringContainsString('function showTodaMembers', $html);
        $this->assertStringContainsString('data-members-url=', $html);
        $this->assertStringContainsString('superadmin/toda', $html);
        $this->assertStringContainsString('onclick="showTodaMembers(' . $toda->id . ',', $html);
    }

    public function test_superadmin_toda_members_endpoint_returns_json()
    {
        $toda = $this->makeToda();
        $opA = $this->makeOperator($toda, 'Alpha Member');
        $opB = $this->makeOperator($toda, 'Beta Member');
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk();

        $resp->assertJsonStructure(['html', 'count']);
        $this->assertEquals(2, $resp->json('count'));
        $this->assertStringContainsString('Alpha Member', $resp->json('html'));
        $this->assertStringContainsString('Beta Member', $resp->json('html'));
        $this->assertStringContainsString('data-member-item', $resp->json('html'));
    }

    public function test_superadmin_toda_members_endpoint_respects_archived()
    {
        $toda = $this->makeToda();
        $opA = $this->makeOperator($toda, 'Alpha Member');
        $opB = $this->makeOperator($toda, 'Beta Member');
        $opB->update(['archived_at' => now()]);
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk();

        $this->assertEquals(1, $resp->json('count'));
        $this->assertStringContainsString('Alpha Member', $resp->json('html'));
        $this->assertStringNotContainsString('Beta Member', $resp->json('html'));
    }

    public function test_toda_members_list_includes_the_toda_president_with_a_badge()
    {
        $toda = $this->makeToda();
        $member = $this->makeOperator($toda, 'Ordinary Member');
        $president = $this->makePresident($toda, 'Rowena President');
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk();

        $html = $resp->json('html');

        // The president is a member of this TODA, so the list shows them.
        $this->assertEquals(2, $resp->json('count'));
        $this->assertStringContainsString('Rowena President', $html);
        $this->assertStringContainsString('Ordinary Member', $html);
        $this->assertStringContainsString('data-member-role="president"', $html);

        // Edit/Archive stay available on the ordinary member only — the account
        // is refused by the backend anyway, so the buttons are withheld.
        $this->assertStringContainsString('/superadmin/operators/' . $member->id . '/edit', $html);
        $this->assertStringContainsString('/superadmin/operators/' . $member->id . '/archive', $html);
        $this->assertStringNotContainsString('/superadmin/operators/' . $president->id . '/edit', $html);
        $this->assertStringNotContainsString('/superadmin/operators/' . $president->id . '/archive', $html);
        $this->assertStringContainsString('Managed under Presidents', $html);
    }

    public function test_tfrb_toda_members_list_also_includes_the_toda_president()
    {
        $toda = $this->makeToda();
        $member = $this->makeOperator($toda, 'Ordinary Member');
        $president = $this->makePresident($toda, 'Rowena President');
        $officer = $this->makeUser('tfrb_officer');

        $resp = $this->actingAs($officer)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/tfrb-officer/toda/' . $toda->id . '/members')
            ->assertOk();

        $html = $resp->json('html');

        $this->assertEquals(2, $resp->json('count'));
        $this->assertStringContainsString('Rowena President', $html);
        $this->assertStringContainsString('data-member-role="president"', $html);
        $this->assertStringContainsString('/tfrb-officer/operators/' . $member->id . '/edit', $html);
        $this->assertStringNotContainsString('/tfrb-officer/operators/' . $president->id . '/edit', $html);
        $this->assertStringNotContainsString('/tfrb-officer/operators/' . $president->id . '/archive', $html);
    }

    public function test_a_president_of_another_toda_is_listed_without_the_presidency_badge()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $elsewhere = $this->makePresident($todaB, 'Beta President');
        // Plain member of TODA A: presidency belongs to TODA B, not this one.
        $elsewhere->joinToda($todaA->id);
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/superadmin/toda/' . $todaA->id . '/members')
            ->assertOk();

        $html = $resp->json('html');

        $this->assertEquals(1, $resp->json('count'));
        $this->assertStringContainsString('Beta President', $html);
        $this->assertStringNotContainsString('data-member-role="president"', $html);
    }

    public function test_toda_activity_card_counts_the_president_like_any_member()
    {
        $toda = $this->makeToda();
        $this->makeOperator($toda, 'Ordinary Member');
        $this->makePresident($toda, 'Rowena President');

        app(\App\Services\AdminDashboardService::class)->flush();

        $stats = collect(app(\App\Services\AdminDashboardService::class)
            ->stats(['includeOfficers' => true])['todaStats']);
        $card = $stats->firstWhere('id', $toda->id);

        // The card total must match what the members modal now lists.
        $this->assertSame(2, (int) $card->operators_count);
        $this->assertSame(2, (int) $card->active_operators_count);
    }
}
