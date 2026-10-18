<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use App\Services\TodaMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Multi-TODA membership workflow with THREE required approvals:
 *   driver request -> TODA president -> TFRB officer -> superadmin final.
 * Also covers the three-TODA limit, duplicate prevention, rejection reasons +
 * driver notifications, QR identity, and backend trip-start validation.
 */
class TodaMembershipTest extends TestCase
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

    private function makeToda(bool $active = true): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => $active,
        ]);
    }

    private function makePresident(Toda $toda): array
    {
        $user = $this->makeUser('operator_president');
        $user->forceFill(['toda_id' => $toda->id])->save();

        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PRES-' . Str::random(6),
            'body_number' => 'P-' . Str::random(6),
        ]);
        $operator->joinToda($toda->id);

        return ['user' => $user, 'operator' => $operator];
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

    private function makeAdmin(string $role = 'superadmin'): User
    {
        return $this->makeUser($role);
    }

    // --- Stage 1: driver request + president review -------------------------

    public function test_operator_can_request_to_join_another_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);

        $this->actingAs($operator->user)
            ->post(route('operator.todas.join'), ['toda_id' => $todaB->id])
            ->assertRedirect();

        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaB->id,
            'status' => OperatorToda::STATUS_PENDING,
        ]);

        // A pending membership is not rateable yet.
        $this->assertFalse($operator->fresh()->getRateableTodas()->contains('id', $todaB->id));
    }

    public function test_operator_cannot_request_a_toda_they_already_belong_to()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);

        $this->actingAs($operator->user)
            ->post(route('operator.todas.join'), ['toda_id' => $toda->id])
            ->assertSessionHas('error');

        $this->assertSame(1, OperatorToda::where('operator_id', $operator->id)
            ->where('toda_id', $toda->id)->count());
    }

    public function test_operator_cannot_request_an_inactive_toda()
    {
        $todaA = $this->makeToda();
        $inactive = $this->makeToda(false);
        $operator = $this->makeOperator($todaA);

        $this->actingAs($operator->user)
            ->post(route('operator.todas.join'), ['toda_id' => $inactive->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $inactive->id,
        ]);
    }

    public function test_guest_cannot_request_to_join()
    {
        $toda = $this->makeToda();

        $this->post(route('operator.todas.join'), ['toda_id' => $toda->id])
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_exceed_three_toda_limit()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $todaC = $this->makeToda();
        $todaD = $this->makeToda();
        $operator = $this->makeOperator($todaA); // home membership = 1

        $service = app(TodaMembershipService::class);
        $service->requestJoin($operator, $todaB);
        $service->requestJoin($operator, $todaC);

        // 3 memberships now (home + B + C); a fourth is refused on the backend.
        $this->assertSame(3, $operator->fresh()->todaMembershipCount());

        $this->actingAs($operator->user)
            ->post(route('operator.todas.join'), ['toda_id' => $todaD->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaD->id,
        ]);
        $this->assertSame(3, $operator->fresh()->todaMembershipCount());
    }

    public function test_president_sees_join_request_for_their_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $pres = $this->makePresident($todaB);

        app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->actingAs($pres['user'])
            ->get(route('president.dashboard'))
            ->assertOk()
            ->assertSee('Join Requests', false)
            ->assertSee($operator->user->name, false);
    }

    public function test_president_cannot_approve_a_request_for_another_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presA = $this->makePresident($todaA);
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->actingAs($presA['user'])
            ->patch(route('president.memberships.approve', $membership))
            ->assertForbidden();

        $this->assertSame(OperatorToda::STATUS_PENDING, $membership->fresh()->status);
    }

    public function test_president_approval_moves_membership_to_pending_tfrb()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->actingAs($presB['user'])
            ->patch(route('president.memberships.approve', $membership))
            ->assertRedirect(route('president.members'));

        $this->assertSame(OperatorToda::STATUS_PENDING_TFRB, $membership->fresh()->status);
        $this->assertFalse($operator->fresh()->getRateableTodas()->contains('id', $todaB->id));
    }

    public function test_president_cannot_approve_their_own_request()
    {
        $todaB = $this->makeToda();
        $presB = $this->makePresident($todaB);

        // The president's own operator row is pushed back to a pending request.
        $presB['operator']->todaMemberships()
            ->where('toda_id', $todaB->id)
            ->update(['status' => OperatorToda::STATUS_PENDING]);
        $membership = $presB['operator']->todaMemberships()->where('toda_id', $todaB->id)->first();

        $this->actingAs($presB['user'])
            ->patch(route('president.memberships.approve', $membership))
            ->assertSessionHas('error');

        $this->assertSame(OperatorToda::STATUS_PENDING, $membership->fresh()->status);
    }

    public function test_president_rejection_records_reason_and_notifies_driver()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->actingAs($presB['user'])
            ->patch(route('president.memberships.reject', $membership), ['note' => 'Not from our area'])
            ->assertRedirect(route('president.members'));

        $membership->refresh();
        $this->assertSame(OperatorToda::STATUS_REJECTED, $membership->status);
        $this->assertSame('Not from our area', $membership->rejectionReason());

        $this->assertDatabaseHas('notifications', [
            'user_id' => $operator->user_id,
            'type' => 'toda_membership',
            'title' => 'TODA Membership Rejected',
        ]);
    }

    // --- Stage 2: TFRB verification -----------------------------------------

    public function test_tfrb_verification_moves_membership_to_pending_superadmin()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);

        $this->actingAs($tfrb)
            ->patch(route('tfrb-officer.operators.memberships.approve', $membership))
            ->assertRedirect();

        $this->assertSame(OperatorToda::STATUS_PENDING_SUPERADMIN, $membership->fresh()->status);
        $this->assertFalse($operator->fresh()->getRateableTodas()->contains('id', $todaB->id));
    }

    public function test_tfrb_cannot_skip_the_president_stage()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->actingAs($tfrb)
            ->patch(route('tfrb-officer.operators.memberships.approve', $membership))
            ->assertSessionHas('error');

        $this->assertSame(OperatorToda::STATUS_PENDING, $membership->fresh()->status);
    }

    public function test_tfrb_can_reject_at_its_stage()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);

        $this->actingAs($tfrb)
            ->patch(route('tfrb-officer.operators.memberships.reject', $membership), ['note' => 'Franchise mismatch'])
            ->assertRedirect();

        $this->assertSame(OperatorToda::STATUS_REJECTED, $membership->fresh()->status);
        $this->assertSame('Franchise mismatch', $membership->fresh()->rejectionReason());
    }

    // --- Stage 3: superadmin final approval ---------------------------------

    public function test_superadmin_final_approval_activates_membership()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $admin = $this->makeAdmin('superadmin');

        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);
        app(TodaMembershipService::class)->tfrbApprove($membership, $tfrb);

        $this->actingAs($admin)
            ->patch(route('superadmin.operators.memberships.approve', $membership))
            ->assertRedirect();

        $this->assertSame(OperatorToda::STATUS_ACTIVE, $membership->fresh()->status);
        $this->assertTrue($operator->fresh()->getRateableTodas()->contains('id', $todaB->id));

        // The driver is notified once the membership is fully approved.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $operator->user_id,
            'type' => 'toda_membership',
            'title' => 'TODA Membership Approved',
        ]);
    }

    public function test_superadmin_cannot_skip_the_tfrb_stage()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $admin = $this->makeAdmin('superadmin');
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);

        $this->actingAs($admin)
            ->patch(route('superadmin.operators.memberships.approve', $membership))
            ->assertSessionHas('error');

        $this->assertSame(OperatorToda::STATUS_PENDING_TFRB, $membership->fresh()->status);
    }

    public function test_superadmin_can_reject_the_final_stage()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $admin = $this->makeAdmin('superadmin');

        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);
        app(TodaMembershipService::class)->tfrbApprove($membership, $tfrb);

        $this->actingAs($admin)
            ->patch(route('superadmin.operators.memberships.reject', $membership), ['note' => 'Documents incomplete'])
            ->assertRedirect();

        $this->assertSame(OperatorToda::STATUS_REJECTED, $membership->fresh()->status);
    }

    public function test_tfrb_sees_verification_card_and_superadmin_sees_final_card()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);
        $tfrb = $this->makeAdmin('tfrb_officer');
        $admin = $this->makeAdmin('superadmin');
        $membership = app(TodaMembershipService::class)->requestJoin($operator, $todaB);
        app(TodaMembershipService::class)->presidentApprove($membership, $presB['user']);

        $this->actingAs($tfrb)
            ->get(route('tfrb-officer.operators'))
            ->assertOk()
            ->assertSee('TODA Membership — TFRB Verification', false)
            ->assertSee($operator->user->name, false);

        // Not yet visible to the superadmin (still at the TFRB stage).
        $this->actingAs($admin)
            ->get(route('superadmin.operators'))
            ->assertOk()
            ->assertDontSee('TODA Membership — Final Approval', false);

        app(TodaMembershipService::class)->tfrbApprove($membership, $tfrb);

        $this->actingAs($admin)
            ->get(route('superadmin.operators'))
            ->assertOk()
            ->assertSee('TODA Membership — Final Approval', false)
            ->assertSee($operator->user->name, false);
    }

    // --- President adds an existing operator ---------------------------------

    public function test_president_can_add_existing_operator_without_new_account()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $presB = $this->makePresident($todaB);

        $userCount = User::count();
        $operatorCount = Operator::count();

        $this->actingAs($presB['user'])
            ->post(route('president.members.addExisting'), ['operator_id' => $operator->id])
            ->assertRedirect(route('president.members'));

        // Same account, same body number — no duplicate created.
        $this->assertSame($userCount, User::count());
        $this->assertSame($operatorCount, Operator::count());

        // President step counts as stage 1 done -> awaits TFRB verification.
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaB->id,
            'status' => OperatorToda::STATUS_PENDING_TFRB,
        ]);
    }

    public function test_president_add_existing_rejects_operator_already_in_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaB->id); // already an active member of B
        $presB = $this->makePresident($todaB);

        $this->actingAs($presB['user'])
            ->post(route('president.members.addExisting'), ['operator_id' => $operator->id])
            ->assertSessionHas('error');
    }

    // --- Admin multi-select + limit -----------------------------------------

    public function test_admin_multi_select_adds_and_removes_memberships()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $todaC = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $admin = $this->makeAdmin('superadmin');

        $this->actingAs($admin)->put(route('superadmin.operators.update', $operator), [
            'name' => $operator->user->name,
            'email' => $operator->user->email,
            'plate_number' => $operator->plate_number,
            'body_number' => $operator->body_number,
            'toda_id' => $todaA->id,
            'toda_ids' => [$todaB->id],
        ])->assertRedirect(route('superadmin.operators'));

        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaB->id,
            'status' => OperatorToda::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->put(route('superadmin.operators.update', $operator), [
            'name' => $operator->user->name,
            'email' => $operator->user->email,
            'plate_number' => $operator->plate_number,
            'body_number' => $operator->body_number,
            'toda_id' => $todaA->id,
            'toda_ids' => [$todaC->id],
        ])->assertRedirect(route('superadmin.operators'));

        $this->assertDatabaseMissing('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaB->id,
        ]);
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaC->id,
            'status' => OperatorToda::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaA->id,
            'status' => OperatorToda::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_multi_select_enforces_three_toda_limit()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $todaC = $this->makeToda();
        $todaD = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $admin = $this->makeAdmin('superadmin');

        // home A + B + C + D = 4 > 3 -> rejected on the backend.
        $this->actingAs($admin)->put(route('superadmin.operators.update', $operator), [
            'name' => $operator->user->name,
            'email' => $operator->user->email,
            'plate_number' => $operator->plate_number,
            'body_number' => $operator->body_number,
            'toda_id' => $todaA->id,
            'toda_ids' => [$todaB->id, $todaC->id, $todaD->id],
        ])->assertSessionHasErrors('toda_ids');

        $this->assertSame(1, $operator->fresh()->todaMembershipCount());
    }

    public function test_edit_form_shows_membership_checkboxes()
    {
        $todaA = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $admin = $this->makeAdmin('superadmin');

        $this->actingAs($admin)
            ->get(route('superadmin.operators.edit', $operator))
            ->assertOk()
            ->assertSee('TODA Memberships', false)
            ->assertSee('name="toda_ids[]"', false);
    }

    // --- President search ----------------------------------------------------

    public function test_president_search_finds_existing_operator()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA, 'active');
        $operator->user->forceFill(['name' => 'Juan Dela Cruz'])->save();
        $presB = $this->makePresident($todaB);

        $response = $this->actingAs($presB['user'])
            ->getJson(route('president.operators.search', ['q' => 'Juan Dela Cruz']))
            ->assertOk();

        $this->assertStringContainsString('Juan Dela Cruz', $response->json('html'));
        $this->assertStringContainsString(route('president.members.addExisting'), $response->json('html'));
    }

    public function test_president_search_excludes_existing_members()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $operator->user->forceFill(['name' => 'Member Mark'])->save();
        $operator->joinToda($todaB->id); // already a member of B
        $presB = $this->makePresident($todaB);

        $response = $this->actingAs($presB['user'])
            ->getJson(route('president.operators.search', ['q' => 'Member Mark']))
            ->assertOk();

        $this->assertStringNotContainsString('Member Mark', $response->json('html'));
    }

    public function test_non_president_cannot_use_the_search_endpoint()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);

        $this->actingAs($operator->user)
            ->getJson(route('president.operators.search', ['q' => 'x']))
            ->assertForbidden();
    }

    // --- QR identity + trip-start validation --------------------------------

    public function test_qr_scan_identifies_driver_with_pending_membership()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        app(TodaMembershipService::class)->requestJoin($operator, $todaB); // pending in B

        // The permanent QR still resolves the driver even though one membership
        // is only pending.
        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee($operator->user->name);
    }

    public function test_backend_rejects_trip_start_under_a_pending_membership()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $todaB->id,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertNotFound();

        $this->assertSame(0, Rating::count());
    }

    public function test_active_membership_is_unaffected_by_a_pending_one()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        app(TodaMembershipService::class)->requestJoin($operator, $todaB);

        $rateable = $operator->fresh()->getRateableTodas();

        // Only the active home TODA is offered; the pending one is excluded.
        $this->assertTrue($rateable->contains('id', $todaA->id));
        $this->assertFalse($rateable->contains('id', $todaB->id));

        // No notification spam for a plain pending request.
        $this->assertSame(0, Notification::where('user_id', $operator->user_id)->count());
    }
}
