<?php

namespace Tests\Feature;

use App\Models\FranchiseTerm;
use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PresidentMemberManagementTest extends TestCase
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

    private function makeOperator(Toda $toda, string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator', 'Operator ' . Str::upper(Str::random(4)));
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

    public function test_president_can_add_member_creating_a_pending_account()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);

        $this->actingAs($pres['user'])->post('/president/members', [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'ABC 1234',
            'body_number' => 'TODA-99',
            'motorcycle_model' => 'Honda Click 125i',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('president.members', ['status' => 'pending']));

        $user = User::where('email', 'newmember@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('operator', $user->role);

        $this->assertDatabaseHas('operators', [
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'status' => 'pending',
            'plate_number' => 'ABC 1234',
            'body_number' => 'TODA-99',
        ]);
    }

    public function test_added_member_appears_in_admin_pending_list()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($pres['user'])->post('/president/members', [
            'name' => 'Pending Juan',
            'email' => 'pendingjuan@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'PEND 001',
            'body_number' => 'PEND-01',
            'motorcycle_model' => 'Yamaha Mio',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->actingAs($admin)
            ->get('/superadmin/operators?status=pending')
            ->assertOk()
            ->assertSee('Pending Juan', false);
    }

    public function test_add_member_rejects_duplicate_plate_number()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $existing = $this->makeOperator($toda);

        $this->actingAs($pres['user'])->post('/president/members', [
            'name' => 'Dupe',
            'email' => 'dupe@example.com',
            'contact_number' => '09171234567',
            'plate_number' => $existing->plate_number,
            'body_number' => 'UNIQUE-01',
            'motorcycle_model' => 'Honda',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('plate_number');
    }

    public function test_non_president_cannot_add_member()
    {
        $operator = $this->makeUser('operator');

        $this->actingAs($operator)->post('/president/members', [
            'name' => 'Nope',
            'email' => 'nope@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'NOPE 01',
            'body_number' => 'NOPE-01',
            'motorcycle_model' => 'Honda',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_president_can_remove_own_toda_member()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        FranchiseTerm::create([
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'franchise_no' => 'FR-REMOVE',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $member->id . '/remove')
            ->assertRedirect(route('president.members'));

        $this->assertNull($member->fresh()->toda_id);
        $this->assertNull($member->user->fresh()->toda_id);
        $this->assertDatabaseHas('franchise_terms', [
            'operator_id' => $member->id,
            'toda_id' => null,
        ]);
    }

    public function test_removed_member_is_no_longer_manageable_by_the_president()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $member->id . '/remove')
            ->assertRedirect();

        // The org boundary now rejects the detached member.
        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $member->id . '/remove')
            ->assertForbidden();
    }

    public function test_president_cannot_remove_member_of_another_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $pres = $this->makePresident($todaA);
        $foreign = $this->makeOperator($todaB);

        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $foreign->id . '/remove')
            ->assertForbidden();

        $this->assertSame($todaB->id, (int) $foreign->fresh()->toda_id);
    }

    public function test_president_cannot_remove_own_account()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);

        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $pres['operator']->id . '/remove')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($toda->id, (int) $pres['operator']->fresh()->toda_id);
    }

    public function test_dashboard_shows_add_member_button_and_modal()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);

        $this->actingAs($pres['user'])
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee('Add Member', false)
            ->assertSee('presidentAddMemberModal', false)
            ->assertSee(route('president.members.store'), false);
    }

    public function test_member_detail_shows_remove_action()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $response = $this->actingAs($pres['user'])
            ->getJson('/president/members/' . $member->id)
            ->assertOk();

        $html = $response->json('html');
        $this->assertStringContainsString('Remove from TODA', $html);
        $this->assertStringContainsString(route('president.members.remove', $member->id), $html);
    }
}
