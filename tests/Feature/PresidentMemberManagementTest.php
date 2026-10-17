<?php

namespace Tests\Feature;

use App\Models\FranchiseTerm;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use App\Services\PresidentQueryService;
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
        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
        $operator->joinToda($toda->id);

        return $operator;
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

        // A TODA membership is registered alongside the account.
        $operatorId = \App\Models\Operator::where('user_id', $user->id)->value('id');
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operatorId,
            'toda_id' => $toda->id,
            'status' => 'active',
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
        $member->joinToda($toda->id);

        // A franchise term must survive the removal (the franchise belongs to
        // the operator, not the membership).
        FranchiseTerm::create([
            'operator_id' => $member->id,
            'franchise_no' => 'FR-REMOVE',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($pres['user'])
            ->delete('/president/members/' . $member->id . '/remove')
            ->assertRedirect(route('president.members'));

        $this->assertNull($member->fresh()->toda_id);
        $this->assertNull($member->user->fresh()->toda_id);
        $this->assertDatabaseMissing('operator_toda', [
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
        ]);
        $this->assertDatabaseHas('franchise_terms', [
            'operator_id' => $member->id,
            'franchise_no' => 'FR-REMOVE',
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

    public function test_member_with_secondary_membership_is_manageable_by_the_second_toda_president()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $member = $this->makeOperator($todaA);   // home TODA
        $member->joinToda($todaB->id);           // also a member of TODA B
        $presB = $this->makePresident($todaB);

        // The second TODA's president can open the member's detail.
        $this->actingAs($presB['user'])
            ->getJson('/president/members/' . $member->id)
            ->assertOk();
    }

    public function test_removing_secondary_membership_keeps_the_home_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $member = $this->makeOperator($todaA);
        $member->joinToda($todaB->id);
        $presB = $this->makePresident($todaB);

        $this->actingAs($presB['user'])
            ->delete('/president/members/' . $member->id . '/remove')
            ->assertRedirect(route('president.members'));

        // Only the secondary membership is gone; the home TODA is untouched.
        $this->assertSame($todaA->id, (int) $member->fresh()->toda_id);
        $this->assertDatabaseMissing('operator_toda', [
            'operator_id' => $member->id,
            'toda_id' => $todaB->id,
        ]);
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $member->id,
            'toda_id' => $todaA->id,
        ]);
    }

    public function test_president_added_member_is_rateable_under_the_toda()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);

        $this->actingAs($pres['user'])->post('/president/members', [
            'name' => 'Rateable Member',
            'email' => 'rateable@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'RATE 001',
            'body_number' => 'RATE-01',
            'motorcycle_model' => 'Honda Beat',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'rateable@example.com')->first();
        $operator = \App\Models\Operator::where('user_id', $user->id)->first();

        $this->assertTrue($operator->getRateableTodas()->contains('id', $toda->id));
    }

    private function makeRating(Operator $operator, Toda $toda, int $stars, string $origin): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'rating' => $stars,
            'start_location' => $origin,
            'end_location' => $origin . ' Terminal',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
            'is_accepted' => true,
        ]);
    }

    public function test_president_member_stats_are_scoped_to_own_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $member = $this->makeOperator($todaA);   // home TODA A
        $member->joinToda($todaB->id);           // also member of TODA B
        $presB = $this->makePresident($todaB);

        // The member carries a 5-star in TODA A and a 1-star in TODA B.
        $this->makeRating($member, $todaA, 5, 'Alpha');
        $this->makeRating($member, $todaB, 1, 'Gamma');

        $svc = app(PresidentQueryService::class);

        $statsB = $svc->memberStats($todaB, $member);
        $this->assertSame(1, (int) $statsB->total);
        $this->assertSame(1.0, (float) $statsB->avg);

        $statsA = $svc->memberStats($todaA, $member);
        $this->assertSame(1, (int) $statsA->total);
        $this->assertSame(5.0, (float) $statsA->avg);
    }

    public function test_president_member_modal_only_exposes_own_toda_ratings()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $member = $this->makeOperator($todaA);
        $member->joinToda($todaB->id);
        $presB = $this->makePresident($todaB);

        $this->makeRating($member, $todaA, 5, 'Alpha');
        $this->makeRating($member, $todaB, 1, 'Gamma');

        $html = $this->actingAs($presB['user'])
            ->getJson('/president/members/' . $member->id)
            ->assertOk()
            ->json('html');

        // Only TODA B's rating (Gamma) may leak into the modal.
        $this->assertStringContainsString('Gamma', $html);
        $this->assertStringNotContainsString('Alpha', $html);
    }
}
