<?php

namespace Tests\Feature;

use App\Models\MemberSuspension;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberSuspensionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'operator', ?string $name = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $name ?? 'Test ' . ucfirst(str_replace('_', ' ', $role)),
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

    private function makeOperator(Toda $toda, string $status = 'active', string $role = 'operator'): Operator
    {
        $user = $this->makeUser($role, 'Operator ' . Str::upper(Str::random(4)));
        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makePresident(Toda $toda): array
    {
        $user = $this->makeUser('operator_president');
        $user->forceFill(['toda_id' => $toda->id])->save();

        $ownOperator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PRESIDENT',
            'body_number' => 'P01',
        ]);

        return ['user' => $user, 'operator' => $ownOperator];
    }

    public function test_president_can_suspend_own_member()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/suspend', [
                'reason' => 'Repeated overcharging reported.',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertEquals('suspended', $member->status);
        $this->assertDatabaseHas('member_suspensions', [
            'operator_id' => $member->id,
            'reason' => 'Repeated overcharging reported.',
        ]);
    }

    public function test_president_member_detail_exposes_compact_suspend_control()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->get('/president/members/' . $member->id)
            ->assertOk()
            ->assertSee('data-suspend-open', false)
            ->assertDontSee('Suspend this member');

        $this->actingAs($pres['user'])
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee('operatorSuspendModal')
            ->assertSee('Suspend Member');
    }

    public function test_president_cannot_suspend_another_toda_member()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $presA = $this->makePresident($todaA);
        $memberB = $this->makeOperator($todaB);

        $this->actingAs($presA['user'])
            ->post('/president/members/' . $memberB->id . '/suspend', [
                'reason' => 'Misconduct',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(3)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_suspended_member_cannot_login_and_is_blocked_by_middleware()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/suspend', [
                'reason' => 'Safety concern',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(2)->toDateString(),
            ])
            ->assertRedirect();

        // Login attempt should fail for suspended operator.
        $this->post('/login', [
            'email' => $member->user->email,
            'password' => 'password123',
        ])->assertRedirect('/');

        $this->assertGuest();

        // Middleware should block dashboard access.
        $this->actingAs($member->user)
            ->get('/operator/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_suspended_member_cannot_be_rated()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/suspend', [
                'reason' => 'Conduct under review',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(4)->toDateString(),
            ]);

        $member->refresh();
        $this->assertEquals('suspended', $member->status);

        $resp = $this->get('/rate/' . $member->qr_code);
        $resp->assertOk();
        $resp->assertSee('Suspended');
        $resp->assertSee('suspended by the TODA');
    }

    public function test_lapsed_suspension_is_auto_reactivated()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/suspend', [
                'reason' => 'Temporary hold',
                'suspended_at' => now()->subDays(5)->toDateString(),
                'suspended_until' => now()->subDays(1)->toDateString(),
            ]);

        $member->refresh();
        $this->assertEquals('suspended', $member->status);

        // Simulate the sync (command or any code path calling it).
        MemberSuspension::query()->active()->whereDate('suspended_until', '<', now()->toDateString())->get();

        \App\Models\Operator::endExpiredSuspensions();

        $member->refresh();
        $this->assertEquals('active', $member->status);
    }

    public function test_president_can_end_suspension_early()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/suspend', [
                'reason' => 'Grace period',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(10)->toDateString(),
            ]);

        $member->refresh();
        $this->assertEquals('suspended', $member->status);

        $this->actingAs($pres['user'])
            ->patch('/president/members/' . $member->id . '/suspend/end')
            ->assertRedirect();

        $member->refresh();
        $this->assertEquals('active', $member->status);

        $suspension = MemberSuspension::where('operator_id', $member->id)->latest('id')->first();
        $this->assertNotNull($suspension->ended_at);
        $this->assertNotNull($suspension->ended_by);
    }
}
