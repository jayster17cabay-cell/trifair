<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use App\Support\TodaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The forced "which TODA are you?" gate shown once per login to any operator or
 * president connected to two or more TODAs, plus the way back into that screen.
 */
class TodaSelectionTest extends TestCase
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
            'name' => 'TODA ' . Str::upper(Str::random(5)),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(User $user, Toda $toda, string $status = 'active'): Operator
    {
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

    /** An operator connected to exactly two TODAs (home + one membership). */
    private function makeMultiTodaOperator(): array
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $todaA);
        $operator->forceFill(['toda_id' => $todaA->id])->save();
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);

        return ['operator' => $operator, 'user' => $user, 'a' => $todaA, 'b' => $todaB];
    }

    public function test_multi_toda_operator_is_held_on_the_picker_until_they_choose(): void
    {
        ['operator' => $operator, 'user' => $user, 'a' => $todaA, 'b' => $todaB] = $this->makeMultiTodaOperator();

        // No TODA picked yet -> every dashboard is held behind the picker.
        $this->actingAs($user)
            ->get('/operator/dashboard')
            ->assertRedirect(route('toda.select'));

        $this->actingAs($user)
            ->get('/toda/select')
            ->assertOk()
            ->assertSee($todaA->name, false)
            ->assertSee($todaB->name, false)
            ->assertSee('Member', false);

        // Picking one records the choice and sends the account onward.
        $this->actingAs($user)
            ->post('/toda/select', ['toda_id' => $todaB->id])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user->fresh())
            ->get('/operator/dashboard')
            ->assertOk()
            ->assertDontSee('Which TODA', false);
    }

    public function test_single_toda_operator_is_never_asked_to_choose(): void
    {
        $toda = $this->makeToda();
        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $toda);
        $operator->joinToda($toda->id);

        $this->actingAs($user)
            ->get('/operator/dashboard')
            ->assertOk();

        // The picker has nothing to offer, so it sends them straight back.
        $this->actingAs($user)
            ->get('/toda/select')
            ->assertRedirect(route('dashboard'));
    }

    public function test_picker_labels_each_toda_with_role_and_suspension(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $todaC = $this->makeToda();

        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);
        $user->forceFill(['toda_id' => $todaA->id])->save();
        $operator->setTodaPresidency($todaA->id, true);
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);
        $operator->joinToda($todaC->id);

        // Suspended in TODA C only — it stays on the list, just flagged.
        $operator->suspend([
            'toda_id' => $todaC->id,
            'suspended_by' => $this->makeUser('superadmin')->id,
            'reason' => 'Safety concern',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($user->fresh())
            ->get('/toda/select')
            ->assertOk()
            ->assertSee($todaA->name, false)
            ->assertSee($todaB->name, false)
            ->assertSee($todaC->name, false)
            ->assertSee('President', false)
            ->assertSee('Suspended', false);
    }

    public function test_dashboard_data_follows_the_toda_that_was_chosen(): void
    {
        ['operator' => $operator, 'user' => $user, 'a' => $todaA, 'b' => $todaB] = $this->makeMultiTodaOperator();

        $this->makeRating($operator, $todaA, 5, 'Alpha');
        $this->makeRating($operator, $todaB, 1, 'Gamma');

        $this->actingAs($user)
            ->post('/toda/select', ['toda_id' => $todaB->id])
            ->assertRedirect(route('dashboard'));

        // Headline follows TODA B (1.0); the overall strip still shows both.
        $this->actingAs($user->fresh())
            ->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('1.0', false)
            ->assertSee('Overall (All TODAs)', false)
            ->assertSee('3.0', false);
    }

    public function test_dashboard_offers_a_way_back_to_the_picker_without_signing_out(): void
    {
        ['user' => $user, 'b' => $todaB] = $this->makeMultiTodaOperator();

        $this->actingAs($user)
            ->post('/toda/select', ['toda_id' => $todaB->id]);

        $backUrl = route('toda.select');

        // The operator app header carries the link back to the picker.
        $this->actingAs($user->fresh())
            ->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('href="' . $backUrl . '"', false);

        // And the picker itself can be reopened at any time mid-session.
        $this->actingAs($user->fresh())
            ->get('/toda/select')
            ->assertOk()
            ->assertSee($todaB->name, false);
    }

    public function test_a_suspended_toda_can_still_be_opened_and_reports_it(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $todaA);
        $operator->forceFill(['toda_id' => $todaA->id])->save();
        $operator->joinToda($todaA->id);
        $operator->joinToda($todaB->id);

        $operator->suspend([
            'toda_id' => $todaA->id,
            'suspended_by' => $this->makeUser('superadmin')->id,
            'reason' => 'Reckless driving',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(7)->toDateString(),
        ]);

        // Picking the suspended TODA is allowed — it just reports the state.
        $this->actingAs($user->fresh())
            ->post('/toda/select', ['toda_id' => $todaA->id])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user->fresh())
            ->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('You are suspended', false)
            ->assertSee('Reckless driving', false)
            ->assertSee($todaA->name, false);

        // The other TODA is untouched by that suspension.
        $this->actingAs($user->fresh())
            ->withSession([TodaContext::SESSION_KEY => $todaB->id])
            ->get('/operator/dashboard')
            ->assertOk()
            ->assertDontSee('You are suspended', false);
    }
}
