<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use App\Services\ComplaintRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Complaints now land with the TODA president of the operator's TODA first
 * (except when the complaint is against a president or an unassigned
 * operator — those go straight to TFRB/superadmin). The president can
 * review, solve or reject it; if they cannot resolve it, they forward it
 * to TFRB/superadmin once, permanently.
 */
class ComplaintRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $name = null): User
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

    private function makePresident(Toda $toda, bool $legacy = false): User
    {
        $user = $this->makeUser('operator_president');
        if ($legacy) {
            $user->forceFill(['toda_id' => $toda->id])->save();

            return $user;
        }

        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PRES-' . Str::random(4),
            'body_number' => 'P' . Str::random(4),
        ]);
        // President of the TODA via the explicit presidency pivot (the same
        // way production or other test suites assign presidencies).
        $operator->setTodaPresidency($toda->id, true);

        return $user;
    }

    private function makeMemberOperator(Toda $toda): Operator
    {
        $user = $this->makeUser('operator');
        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
        $operator->joinToda($toda->id);

        return $operator;
    }

    private function makeComplaint(Operator $operator, ?Toda $toda = null, int $stars = 1): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'toda_id' => $toda?->id,
            'rating' => $stars,
            'complaint_type' => 'Rude Driver',
            'complaint_details' => 'Rude to passenger',
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
            'passenger_email' => 'passenger@example.facebook.com',
        ]);
    }

    // ---- routing classifier ------------------------------------------------

    public function test_member_complaint_routes_to_toda_president()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);

        $assignee = app(ComplaintRoutingService::class)->assigneeIdForRating($complaint);

        $this->assertSame($president->id, $assignee);
    }

    public function test_president_complaint_routes_to_tfrb()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $presidentOperator = $president->operator;
        $complaint = $this->makeComplaint($presidentOperator, $toda);

        $assignee = app(ComplaintRoutingService::class)->assigneeIdForRating($complaint);

        $this->assertNull($assignee);
    }

    public function test_unassigned_operator_complaint_routes_to_tfrb()
    {
        $operator = $this->makeMemberOperator($this->makeToda());
        $operator->update(['status' => 'inactive']);
        $operator->todaMemberships()->delete();
        $complaint = $this->makeComplaint($operator, null);

        $computed = app(ComplaintRoutingService::class)->assigneeIdForRating($complaint);

        $this->assertNull($computed);
    }

    public function test_toda_without_president_routes_to_tfrb()
    {
        $toda = $this->makeToda();
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);

        $assignee = app(ComplaintRoutingService::class)->assigneeIdForRating($complaint);

        $this->assertNull($assignee);
    }

    // ---- president acts on routed complaint ---------------------------------

    public function test_president_page_shows_assigned_complaints()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($president)
            ->get('/president/complaints')
            ->assertOk()
            ->assertSee('Complaints Queue')
            ->assertSee($complaint->reference_number, false);
    }

    public function test_president_can_review_and_solve_routed_complaint()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/review')
            ->assertRedirect();

        $this->assertTrue((bool) $complaint->fresh()->is_reviewed);
        $this->assertSame($president->id, $complaint->fresh()->reviewed_by);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/solve', [
                'resolution_note' => 'Spoke with the driver and warned them.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertTrue((bool) $fresh->is_solved);
        $this->assertSame('Spoke with the driver and warned them.', $fresh->resolution_note);
    }

    public function test_president_can_reject_routed_complaint()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/reject', [
                'resolution_note' => 'Unverifiable claim.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertNotNull($fresh->rejected_at);
        $this->assertNull($fresh->solved_at);
        $this->assertSame('Unverifiable claim.', $fresh->resolution_note);
    }

    public function test_other_president_cannot_act_on_assigned_complaint()
    {
        $todaA = $this->makeToda();
        $presidentA = $this->makePresident($todaA);
        $todaB = $this->makeToda();
        $presidentB = $this->makePresident($todaB);
        $operator = $this->makeMemberOperator($todaA);
        $complaint = $this->makeComplaint($operator, $todaA);
        $complaint->update(['routed_to' => $presidentA->id]);

        $this->actingAs($presidentB)
            ->patch('/president/complaints/' . $complaint->id . '/solve')
            ->assertForbidden();
    }

    // ---- forwarding is permanent --------------------------------------------

    public function test_president_can_forward_complaint_to_tfrb()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/forward')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertNotNull($fresh->forwarded_at);
        $this->assertSame($president->id, $fresh->forwarded_by);

        // TFRB/superadmin get notified so the case reaches their queue.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $complaint->id,
            'type' => 'complaint',
            'title' => 'Complaint Forwarded by President',
        ]);
    }

    public function test_president_forward_of_solved_complaint_escalates_it_back()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update([
            'routed_to' => $president->id,
            'is_reviewed' => true,
            'is_solved' => true,
            'solved_at' => now(),
            'resolution_note' => 'Resolved already.',
        ]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/forward')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertNotNull($fresh->forwarded_at);

        // Escalation: forwarding a solved complaint resets the terminal
        // solved state so the case re-enters the actionable TFRB / superadmin
        // queue for a heavier re-review.
        $this->assertFalse((bool) $fresh->is_solved);
        $this->assertFalse((bool) $fresh->is_reviewed);
        $this->assertNull($fresh->solved_at);
    }

    public function test_forwarded_complaint_cannot_be_acted_on_by_president()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id, 'forwarded_at' => now()]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/solve')
            ->assertForbidden();
    }

    public function test_forwarded_complaint_is_actionable_by_tfrb()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id, 'forwarded_at' => now()]);

        $this->actingAs($officer)
            ->patch('/tfrb-officer/complaints/' . $complaint->id . '/solve', [
                'resolution_note' => 'TFRB resolved after escalation.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertTrue((bool) $fresh->is_solved);
        $this->assertSame('TFRB resolved after escalation.', $fresh->resolution_note);
    }

    public function test_tfrb_cannot_act_on_unforwarded_routed_complaint()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($officer)
            ->patch('/tfrb-officer/complaints/' . $complaint->id . '/solve')
            ->assertForbidden();
    }

    public function test_direct_tfrb_cases_remain_actionable_by_tfrb()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $complaint = $this->makeComplaint($president->operator, $toda);

        $this->actingAs($officer)
            ->patch('/tfrb-officer/complaints/' . $complaint->id . '/solve', [
                'resolution_note' => 'Direct to TFRB case.',
            ])
            ->assertRedirect();

        $this->assertTrue((bool) $complaint->fresh()->is_solved);
    }

    // ---- queue visibility & lock badge ----------------------------------------

    public function test_president_queue_hides_locks_and_forwarded_note()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);

        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->actingAs($president)
            ->get('/president/complaints')
            ->assertOk()
            ->assertSee('How complaints flow')
            ->assertSee('Forward to TFRB', false);
    }

    // ---- new complaint gets routed on creation --------------------------------

    public function test_submitting_complaint_routes_to_toda_president()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 1,
            'complaint_type' => 'Rude Driver',
            'start_location' => 'Municipal Hall, Solano',
            'end_location' => 'Market Road, Solano',
            'passenger_email' => 'rider@example.com',
            'toda_id' => $toda->id,
            'proofs' => [\Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
        ])->assertRedirect();

        $complaint = Rating::where('operator_id', $operator->id)->whereNotNull('complaint_type')->first();
        $this->assertNotNull($complaint);
        $this->assertSame($president->id, (int) $complaint->routed_to);

        // The president gets the notification; TFRB/superadmin do not.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $president->id,
            'rating_id' => $complaint->id,
            'type' => 'complaint',
        ]);
        $officer = $this->makeUser('tfrb_officer');
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $complaint->id,
            'type' => 'complaint',
        ]);
    }

    public function test_submitting_complaint_against_president_notifies_tfrb()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $officer = $this->makeUser('tfrb_officer');
        $presidentOperator = $president->operator;

        $this->post('/rate/' . $presidentOperator->qr_code, [
            'rating' => 1,
            'complaint_type' => 'Rude Driver',
            'start_location' => 'Municipal Hall, Solano',
            'end_location' => 'Market Road, Solano',
            'passenger_email' => 'rider@example.com',
            'toda_id' => $toda->id,
            'proofs' => [\Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
        ])->assertRedirect();

        $complaint = Rating::where('operator_id', $presidentOperator->id)->whereNotNull('complaint_type')->first();
        $this->assertNotNull($complaint);
        $this->assertNull($complaint->routed_to);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $complaint->id,
            'type' => 'complaint',
        ]);
    }

    public function test_reviewed_president_complaint_counts_toward_average()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);

        Rating::create([
            'operator_id' => $operator->id,
            'rating' => 5,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_auto' => false,
        ]);
        $complaint = $this->makeComplaint($operator, $toda);
        $complaint->update(['routed_to' => $president->id]);

        $this->assertEqualsWithDelta(3.0, (float) $operator->averageRating(), 0.001);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/review')
            ->assertRedirect();

        $this->assertEqualsWithDelta(3.0, (float) $operator->averageRating(), 0.001);
    }

    // ---- staff complaints queue: source tabs ----------------------------------

    /**
     * Builds one complaint of each source and returns [forwarded, direct,
     * awaiting, president].
     *
     * @return array{0: Rating, 1: Rating, 2: Rating, 3: User}
     */
    private function makeComplaintsOfEverySource(): array
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);

        $forwarded = $this->makeComplaint($operator, $toda);
        $forwarded->update([
            'routed_to' => $president->id,
            'forwarded_at' => now(),
            'forwarded_by' => $president->id,
        ]);

        $direct = $this->makeComplaint($operator, $toda);
        $direct->update(['routed_to' => null, 'forwarded_at' => null]);

        $awaiting = $this->makeComplaint($operator, $toda);
        $awaiting->update(['routed_to' => $president->id, 'forwarded_at' => null]);

        return [$forwarded->fresh(), $direct->fresh(), $awaiting->fresh(), $president];
    }

    public function test_staff_default_queue_shows_only_actionable_complaints()
    {
        [$forwarded, $direct, $awaiting] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/complaints')
            ->assertOk()
            // Forwarded escalations and direct-to-TFRB cases are actionable.
            ->assertSee($forwarded->reference_number, false)
            ->assertSee($direct->reference_number, false)
            // Still with the president — read-only, so it is hidden by default.
            ->assertDontSee($awaiting->reference_number, false)
            ->assertSee('Forwarded by President')
            ->assertSee('Direct to TFRB');
    }

    public function test_staff_forwarded_tab_lists_escalations_with_sender()
    {
        [$forwarded, $direct, $awaiting, $president] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/complaints?source=forwarded')
            ->assertOk()
            ->assertSee($forwarded->reference_number, false)
            ->assertDontSee($direct->reference_number, false)
            ->assertDontSee($awaiting->reference_number, false)
            ->assertSee('Forwarded by President')
            ->assertSee('Escalated by')
            ->assertSee($president->name);
    }

    public function test_staff_awaiting_tab_lists_cases_still_with_president()
    {
        [$forwarded, $direct, $awaiting, $president] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/complaints?source=awaiting')
            ->assertOk()
            ->assertSee($awaiting->reference_number, false)
            ->assertDontSee($forwarded->reference_number, false)
            ->assertDontSee($direct->reference_number, false)
            ->assertSee('With ' . $president->name);
    }

    public function test_staff_direct_tab_lists_only_direct_complaints()
    {
        [$forwarded, $direct, $awaiting] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/complaints?source=direct')
            ->assertOk()
            ->assertSee($direct->reference_number, false)
            ->assertDontSee($forwarded->reference_number, false)
            ->assertDontSee($awaiting->reference_number, false)
            ->assertSee('Direct to TFRB');
    }

    public function test_staff_all_tab_shows_every_source()
    {
        [$forwarded, $direct, $awaiting] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/complaints?source=all')
            ->assertOk()
            ->assertSee($forwarded->reference_number, false)
            ->assertSee($direct->reference_number, false)
            ->assertSee($awaiting->reference_number, false);
    }

    public function test_source_counts_endpoint_returns_live_tallies()
    {
        [$forwarded, $direct, $awaiting] = $this->makeComplaintsOfEverySource();
        $admin = $this->makeUser('superadmin');

        // Three complaints exist: 1 forwarded, 1 direct, 1 awaiting. Under
        // the default "open" filter all three are open, so actionable = 2
        // (forwarded + direct), forwarded = 1, direct = 1, awaiting = 1.
        $this->actingAs($admin)
            ->getJson('/superadmin/complaints/source-counts')
            ->assertOk()
            ->assertJsonStructure(['source', 'sourceCounts', 'filter', 'rating']);
        $payload = $this->getJson('/superadmin/complaints/source-counts')->assertOk()->json();
        $this->assertSame('actionable', $payload['source']);
        $this->assertSame('open', $payload['filter']);
        $this->assertSame(['actionable' => 2, 'forwarded' => 1, 'direct' => 1, 'awaiting' => 1, 'all' => 3], $payload['sourceCounts']);
    }

    public function test_source_counts_endpoint_is_staff_only()
    {
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);

        $this->actingAs($president)->getJson('/superadmin/complaints/source-counts')->assertStatus(403);
    }

    public function test_president_queue_shows_forwarded_handoff_summary()
    {
        [$forwarded, $direct, $awaiting, $president] = $this->makeComplaintsOfEverySource();

        $this->actingAs($president)
            ->get('/president/complaints')
            ->assertOk()
            ->assertSee('You forwarded 1 complaint — handed over to the TFRB for handling');
    }
}
