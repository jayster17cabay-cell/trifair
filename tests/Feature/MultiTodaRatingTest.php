<?php

namespace Tests\Feature;

use App\Models\FranchiseTerm;
use App\Models\MemberSuspension;
use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiTodaRatingTest extends TestCase
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

    private function makeToda(?string $name = null): Toda
    {
        return Toda::create([
            'name' => $name ?? 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
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

    /**
     * The operator's single LGU franchise (operator-wide, not per TODA).
     */
    private function makeTerm(Operator $operator, bool $expired = false): FranchiseTerm
    {
        return FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-' . Str::upper(Str::random(5)),
            'issued_at' => now()->subYear()->toDateString(),
            'valid_until' => ($expired ? now()->subDay() : now()->addYear())->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);
    }

    public function test_operator_with_single_toda_goes_straight_to_rating_form()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);
        $operator->joinToda($toda->id);
        $this->makeTerm($operator);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('Select the TODA')
            ->assertSee("How's my ride?", false);
    }

    public function test_operator_with_multiple_todas_shows_selection_page()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Select the TODA')
            ->assertSee('Alpha TODA')
            ->assertSee('Beta TODA');
    }

    public function test_selecting_a_toda_renders_form_scoped_to_that_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertOk()
            ->assertSee('Beta TODA')
            ->assertSee('name="toda_id"', false);
    }

    public function test_submitting_rating_records_the_selected_toda_and_term()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $term = $this->makeTerm($operator);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $todaB->id,
            'start_location' => 'Quirino Highway, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertSame($todaB->id, (int) $rating->toda_id);
        // One franchise per operator: the rating always points at that term.
        $this->assertSame($term->id, (int) $rating->franchise_term_id);
    }

    public function test_submitting_without_toda_when_multiple_exists_redirects_to_selection()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertRedirect(route('rate.operator', $operator->qr_code));

        $this->assertSame(0, Rating::count());
    }

    public function test_unknown_toda_id_returns_404()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $this->makeTerm($operator);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertNotFound();
    }

    public function test_suspension_in_one_toda_does_not_block_the_other_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        // Suspended only under TODA A (status flips globally, but the block is
        // scoped to the TODA the president acted under).
        MemberSuspension::create([
            'operator_id' => $operator->id,
            'toda_id' => $todaA->id,
            'suspended_by' => $this->makeUser('operator_president')->id,
            'reason' => 'Overcharging.',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(5)->toDateString(),
        ]);
        $operator->update(['status' => 'suspended']);

        // TODA A is blocked...
        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaA->id]))
            ->assertOk()
            ->assertSee('Suspended', false);

        // ...but TODA B still rates normally.
        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertOk()
            ->assertSee('Beta TODA')
            ->assertDontSee('Suspended', false);
    }

    public function test_suspended_toda_is_hidden_from_the_picker()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $todaC = $this->makeToda('Gamma TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $operator->joinToda($todaC->id);
        $this->makeTerm($operator);

        // Suspended only under TODA A.
        MemberSuspension::create([
            'operator_id' => $operator->id,
            'toda_id' => $todaA->id,
            'suspended_by' => $this->makeUser('operator_president')->id,
            'reason' => 'Overcharging.',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(5)->toDateString(),
        ]);
        $operator->update(['status' => 'suspended']);

        // The picker offers only the non-suspended TODAs; TODA A is hidden.
        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Select the TODA')
            ->assertSee('Beta TODA')
            ->assertSee('Gamma TODA')
            ->assertDontSee('Alpha TODA');
    }

    public function test_picker_shows_all_todas_once_a_suspension_expires()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        // A suspension that ended yesterday (row not yet reconciled).
        MemberSuspension::create([
            'operator_id' => $operator->id,
            'toda_id' => $todaA->id,
            'suspended_by' => $this->makeUser('operator_president')->id,
            'reason' => 'Overcharging.',
            'suspended_at' => now()->subDays(10)->toDateString(),
            'suspended_until' => now()->subDay()->toDateString(),
        ]);
        $operator->update(['status' => 'suspended']);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Select the TODA')
            ->assertSee('Alpha TODA')
            ->assertSee('Beta TODA');
    }

    public function test_suspended_under_every_toda_shows_notice_not_picker()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        foreach ([$todaA, $todaB] as $toda) {
            MemberSuspension::create([
                'operator_id' => $operator->id,
                'toda_id' => $toda->id,
                'suspended_by' => $this->makeUser('operator_president')->id,
                'reason' => 'Overcharging.',
                'suspended_at' => now()->toDateString(),
                'suspended_until' => now()->addDays(5)->toDateString(),
            ]);
        }
        $operator->update(['status' => 'suspended']);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Suspended', false)
            ->assertDontSee('Select the TODA');
    }

    public function test_expired_franchise_blocks_every_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        // A single franchise, now lapsed — it must block all TODAs at once.
        $this->makeTerm($operator, true);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaA->id]))
            ->assertOk()
            ->assertSee('Franchise Expired', false);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertOk()
            ->assertSee('Franchise Expired', false);
    }

    public function test_legacy_operator_without_membership_falls_back_to_home_toda()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);
        // No operator_toda rows (legacy) — falls back to the home toda_id.
        $this->makeTerm($operator);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('Select the TODA');

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertSame($toda->id, (int) $rating->toda_id);
    }

    /**
     * Create a live (not yet lapsed) suspension for the operator under a TODA.
     */
    private function suspendUnder(Operator $operator, Toda $toda): MemberSuspension
    {
        return MemberSuspension::create([
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'suspended_by' => $this->makeUser('operator_president')->id,
            'reason' => 'Overcharging.',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(5)->toDateString(),
        ]);
    }

    public function test_single_toda_suspension_shows_identity_and_blocks_the_trip()
    {
        $toda = $this->makeToda('Solo TODA');
        $operator = $this->makeOperator($toda);
        $operator->joinToda($toda->id);
        $this->makeTerm($operator);

        $this->suspendUnder($operator, $toda);
        $operator->update(['status' => 'suspended']);

        // The driver is still identifiable, and the suspension is explained;
        // the trip (rating) form must not be offered.
        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee($operator->user->name)
            ->assertSee('Suspended', false)
            ->assertDontSee("How's my ride?", false);

        // A direct backend POST cannot start a trip under the suspended TODA.
        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $toda->id,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertSee('Suspended', false);

        $this->assertSame(0, Rating::count());
    }

    public function test_scan_with_two_todas_one_suspended_offers_only_the_active_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->suspendUnder($operator, $todaA);
        $operator->update(['status' => 'suspended']);

        // Scanning the permanent QR offers only the active TODA, straight to
        // the trip form — the suspended TODA is neither offered nor shown, and
        // no picker is needed for a single available TODA.
        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Beta TODA')
            ->assertDontSee('Alpha TODA')
            ->assertDontSee('Select the TODA')
            ->assertSee("How's my ride?", false);
    }

    public function test_direct_post_cannot_start_a_trip_under_a_suspended_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->suspendUnder($operator, $todaA);
        $operator->update(['status' => 'suspended']);

        // Frontend filtering is bypassed here: a crafted POST straight at the
        // suspended TODA is refused by the server and records nothing.
        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $todaA->id,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertSee('Suspended', false);
        $this->assertSame(0, Rating::count());

        // The active TODA still starts a trip normally.
        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $todaB->id,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $this->assertSame(1, Rating::count());
        $this->assertSame($todaB->id, (int) Rating::first()->toda_id);
    }

    public function test_three_todas_one_suspended_offers_only_the_two_active()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $todaC = $this->makeToda('Gamma TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $operator->joinToda($todaC->id);
        $this->makeTerm($operator);

        $this->suspendUnder($operator, $todaA);
        $operator->update(['status' => 'suspended']);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Select the TODA')
            ->assertSee('Beta TODA')
            ->assertSee('Gamma TODA')
            ->assertDontSee('Alpha TODA');
    }

    public function test_all_todas_suspended_keeps_identity_and_blocks_every_trip()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $todaC = $this->makeToda('Gamma TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $operator->joinToda($todaC->id);
        $this->makeTerm($operator);

        foreach ([$todaA, $todaB, $todaC] as $toda) {
            $this->suspendUnder($operator, $toda);
        }
        $operator->update(['status' => 'suspended']);

        // The driver stays identifiable, but no TODA is available for a trip.
        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee($operator->user->name)
            ->assertSee('Suspended', false)
            ->assertDontSee('Select the TODA')
            ->assertDontSee("How's my ride?", false);

        foreach ([$todaA, $todaB, $todaC] as $toda) {
            $this->post(route('rate.submit', $operator->qr_code), [
                'rating' => 5,
                'toda_id' => $toda->id,
                'start_location' => 'A',
                'end_location' => 'B',
            ])->assertSee('Suspended', false);
        }

        $this->assertSame(0, Rating::count());
    }

    public function test_reactivated_toda_reappears_in_the_selection()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        $this->suspendUnder($operator, $todaA);
        $operator->update(['status' => 'suspended']);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('Alpha TODA');

        // The president officially lifts the suspension: the assignment is
        // available again.
        $operator->endSuspension();

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Select the TODA')
            ->assertSee('Alpha TODA')
            ->assertSee('Beta TODA');
    }

    public function test_suspended_notice_names_the_toda_that_was_suspended()
    {
        $todaA = $this->makeToda('Alpha TODA'); // the driver's home TODA
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $this->makeTerm($operator);

        // Suspended only under Beta, which is NOT the driver's home TODA.
        $this->suspendUnder($operator, $todaB);
        $operator->update(['status' => 'suspended']);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertOk()
            ->assertSee('Suspended', false)
            ->assertSee('Beta TODA')
            ->assertDontSee('Alpha TODA');
    }

    public function test_withdrawn_membership_is_not_treated_as_active()
    {
        $toda = $this->makeToda('Withdrawn TODA');
        $operator = $this->makeOperator($toda); // home toda_id points here
        $this->makeTerm($operator);
        // A retained-but-not-active membership row, and no active rows.
        $operator->joinToda($toda->id, OperatorToda::STATUS_INACTIVE);

        // The home toda_id must not be silently offered as an active assignment.
        $this->assertTrue($operator->getRateableTodas()->isEmpty());

        // A direct POST to the withdrawn TODA cannot start a trip either.
        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $toda->id,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertNotFound();

        $this->assertSame(0, Rating::count());
    }
}
