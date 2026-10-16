<?php

namespace Tests\Feature;

use App\Models\FranchiseTerm;
use App\Models\MemberSuspension;
use App\Models\Operator;
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

    private function makeTerm(Operator $operator, Toda $toda, bool $expired = false): FranchiseTerm
    {
        return FranchiseTerm::create([
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'franchise_no' => 'FR-' . Str::upper(Str::random(5)),
            'issued_at' => now()->toDateString(),
            'valid_until' => ($expired ? now()->subDay() : now()->addYear())->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);
    }

    public function test_operator_with_single_toda_goes_straight_to_rating_form()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);
        $this->makeTerm($operator, $toda);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('Piliin ang TODA')
            ->assertSee("How's my ride?", false);
    }

    public function test_operator_with_multiple_todas_shows_selection_page()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $this->makeTerm($operator, $todaA);
        $this->makeTerm($operator, $todaB);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Piliin ang TODA')
            ->assertSee('Alpha TODA')
            ->assertSee('Beta TODA');
    }

    public function test_selecting_a_toda_renders_form_scoped_to_that_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $this->makeTerm($operator, $todaA);
        $this->makeTerm($operator, $todaB);

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
        $termA = $this->makeTerm($operator, $todaA);
        $termB = $this->makeTerm($operator, $todaB);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'toda_id' => $todaB->id,
            'start_location' => 'Quirino Highway, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertSame($todaB->id, (int) $rating->toda_id);
        $this->assertSame($termB->id, (int) $rating->franchise_term_id);
        $this->assertNotSame($termA->id, (int) $rating->franchise_term_id);
    }

    public function test_submitting_without_toda_when_multiple_exists_redirects_to_selection()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $operator = $this->makeOperator($todaA);
        $this->makeTerm($operator, $todaA);
        $this->makeTerm($operator, $todaB);

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
        $this->makeTerm($operator, $todaA);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertNotFound();
    }

    public function test_suspension_in_one_toda_does_not_block_the_other_toda()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $this->makeTerm($operator, $todaA);
        $this->makeTerm($operator, $todaB);

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

    public function test_expired_franchise_in_one_toda_does_not_block_the_other()
    {
        $todaA = $this->makeToda('Alpha TODA');
        $todaB = $this->makeToda('Beta TODA');
        $operator = $this->makeOperator($todaA);
        $this->makeTerm($operator, $todaA, true); // expired
        $this->makeTerm($operator, $todaB);       // valid

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaA->id]))
            ->assertOk()
            ->assertSee('Franchise Expired', false);

        $this->get(route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $todaB->id]))
            ->assertOk()
            ->assertSee('Beta TODA')
            ->assertDontSee('Franchise Expired', false);
    }

    public function test_legacy_term_without_toda_still_applies_to_single_toda_operator()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);
        // Legacy term created before per-TODA tagging (toda_id null).
        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-LEGACY',
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('Piliin ang TODA');

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertSame($toda->id, (int) $rating->toda_id);
    }
}
