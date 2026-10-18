<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Toda;
use App\Models\User;
use App\Support\TodaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Per-TODA presidency: a single account can preside over one TODA while merely
 * being a member of another (or preside over several). Presidency is a flag on
 * the operator_toda membership, scoped by the session's "current TODA", and the
 * dashboard switches between the president console and the plain operator view.
 */
class MultiTodaPresidentTest extends TestCase
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

    /** Give an existing operator the presidency of a TODA (flag on the pivot). */
    private function makePresidentOf(User $user, Operator $operator, Toda $toda): void
    {
        $user->forceFill([
            'role' => 'operator_president',
            'toda_id' => $user->toda_id ?: $toda->id,
        ])->save();
        $operator->setTodaPresidency($toda->id, true);
    }

    public function test_assign_president_flags_the_membership_and_toda_relation(): void
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeOperator($this->makeUser('operator'), $toda);

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/assign-president')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertSame('operator_president', $operator->user->fresh()->role);
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'is_president' => true,
            'status' => OperatorToda::STATUS_ACTIVE,
        ]);

        $toda->refresh();
        $this->assertSame(1, $toda->president()->count());
        $this->assertSame($operator->user_id, $toda->presidentUser()->id);
    }

    public function test_toda_president_relation_uses_the_flag_not_users_toda_id(): void
    {
        $toda = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $toda);
        $operator->setTodaPresidency($toda->id, true);

        // users.toda_id deliberately left pointing nowhere: only the flag counts.
        $this->assertNull($user->fresh()->toda_id);
        $this->assertSame(1, $toda->president()->count());
        $this->assertSame($user->id, $toda->presidentUser()->id);
    }

    public function test_remove_president_clears_the_flag(): void
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $user->forceFill(['toda_id' => $toda->id])->save();
        $operator = $this->makeOperator($user, $toda);
        $operator->setTodaPresidency($toda->id, true);

        $this->actingAs($admin)
            ->delete('/superadmin/presidents/' . $user->id)
            ->assertRedirect(route('superadmin.presidents'));

        $this->assertSame('operator', $user->fresh()->role);
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'is_president' => false,
        ]);
        $this->assertSame(0, $toda->fresh()->president()->count());
    }

    public function test_one_account_can_preside_over_two_todas(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);

        $this->makePresidentOf($user, $operator, $todaA);

        // Promote the same account over a second TODA through the admin action.
        $admin = $this->makeUser('superadmin');
        $operator->update(['toda_id' => $todaB->id, 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/assign-president')
            ->assertRedirect(route('superadmin.operators'));

        $user = $user->fresh();
        $this->assertCount(2, $user->presidentTodas());
        $this->assertTrue($user->isPresidentOf($todaA->id));
        $this->assertTrue($user->isPresidentOf($todaB->id));
        $this->assertSame(1, $todaA->fresh()->president()->count());
        $this->assertSame(1, $todaB->fresh()->president()->count());
    }

    public function test_dashboard_defaults_to_a_presidency_toda(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);
        $this->makePresidentOf($user, $operator, $todaA);
        // Ordinary member of a second TODA (no presidency there).
        $operator->joinToda($todaB->id);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertRedirect(route('president.dashboard'));
    }

    public function test_dashboard_opens_operator_view_when_current_toda_is_membership_only(): void
    {
        $todaA = $this->makeToda(); // presidency
        $todaB = $this->makeToda(); // membership only
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);
        $this->makePresidentOf($user, $operator, $todaA);
        $operator->joinToda($todaB->id);

        $this->actingAs($user->fresh())
            ->withSession([TodaContext::SESSION_KEY => $todaB->id])
            ->get('/dashboard')
            ->assertRedirect(route('operator.dashboard', ['toda' => $todaB->id]));
    }

    public function test_switch_toda_updates_session_and_rejects_unconnected_toda(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $other = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);
        $this->makePresidentOf($user, $operator, $todaA);
        $operator->joinToda($todaB->id);

        $this->actingAs($user->fresh())
            ->post('/toda/switch', ['toda_id' => $todaB->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($todaB->id, session(TodaContext::SESSION_KEY));

        // A TODA the user is not connected to is refused.
        $this->actingAs($user->fresh())
            ->from('/dashboard')
            ->post('/toda/switch', ['toda_id' => $other->id])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error');
    }

    public function test_president_console_scopes_to_the_current_presidency(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $user = $this->makeUser('operator_president', 'President Multi');
        $operator = $this->makeOperator($user, $todaA);
        $this->makePresidentOf($user, $operator, $todaA);
        $operator->update(['toda_id' => $todaB->id]);
        $operator->setTodaPresidency($todaB->id, true);

        // A member sits only in TODA B.
        $memberUser = $this->makeUser('operator', 'Member Bee');
        $member = $this->makeOperator($memberUser, $todaB);
        $member->joinToda($todaB->id);

        $this->actingAs($user->fresh())
            ->withSession([TodaContext::SESSION_KEY => $todaB->id])
            ->get('/president/members')
            ->assertOk()
            ->assertSee($todaB->name)
            ->assertSee('Member Bee');
    }

    public function test_suspended_presidency_falls_back_to_another_unsuspended_toda(): void
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $user = $this->makeUser('operator_president');
        $operator = $this->makeOperator($user, $todaA);
        $this->makePresidentOf($user, $operator, $todaA);
        $operator->update(['toda_id' => $todaB->id]);
        $operator->setTodaPresidency($todaB->id, true); // presidency of both

        // Suspended only for TODA A.
        $operator->suspend([
            'toda_id' => $todaA->id,
            'suspended_by' => $this->makeUser('superadmin')->id,
            'reason' => 'Misconduct',
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user->fresh())
            ->withSession([TodaContext::SESSION_KEY => $todaA->id])
            ->get('/president/dashboard');

        $response->assertRedirect(route('president.dashboard'));
        $this->assertSame($todaB->id, session(TodaContext::SESSION_KEY));

        // The fallback TODA's console now opens.
        $this->actingAs($user->fresh())
            ->get('/president/dashboard')
            ->assertOk();
    }

    public function test_operators_list_shows_every_toda_the_operator_belongs_to(): void
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $operator = $this->makeOperator($this->makeUser('operator'), $todaA);
        $operator->joinToda($todaB->id);

        $this->actingAs($admin)
            ->get('/superadmin/operators')
            ->assertOk()
            ->assertSee($todaA->name, false)
            ->assertSee($todaB->name, false);
    }

    public function test_assign_president_can_target_a_chosen_toda(): void
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $todaA);
        $operator->joinToda($todaB->id);

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/assign-president', ['toda_id' => $todaB->id])
            ->assertRedirect(route('superadmin.operators'))
            ->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertSame('operator_president', $user->role);
        $this->assertTrue($user->isPresidentOf($todaB->id));
        $this->assertFalse($user->isPresidentOf($todaA->id));

        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $todaB->id,
            'is_president' => true,
        ]);
        $this->assertSame(1, $todaB->fresh()->president()->count());
        $this->assertSame(0, $todaA->fresh()->president()->count());
    }

    public function test_assign_president_rejects_a_toda_the_operator_is_not_a_member_of(): void
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $todaA); // member of A only

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/assign-president', ['toda_id' => $todaB->id])
            ->assertRedirect(route('superadmin.operators'))
            ->assertSessionHas('error');

        $this->assertSame('operator', $user->fresh()->role);
        $this->assertSame(0, $todaB->fresh()->president()->count());
    }

    /** Rate a driver under a specific TODA so per-TODA averages can be asserted. */
    private function rate(Operator $operator, Toda $toda, int $stars): void
    {
        $operator->ratings()->create([
            'toda_id' => $toda->id,
            'rating' => $stars,
            'reason' => 'Trip feedback',
            'start_location' => 'Pickup',
            'end_location' => 'Dropoff',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    public function test_president_dashboard_separates_own_ratings_per_toda_with_total(): void
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $user = $this->makeUser('operator');
        $operator = $this->makeOperator($user, $todaA);
        $operator->joinToda($todaB->id);

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/assign-president', ['toda_id' => $todaA->id]);

        // 5 stars under TODA A, 1 star under TODA B -> 3.0 combined.
        $this->rate($operator, $todaA, 5);
        $this->rate($operator, $todaA, 5);
        $this->rate($operator, $todaB, 1);

        $this->actingAs($user->fresh())
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee('Per TODA', false)
            ->assertSee($todaA->name, false)
            ->assertSee($todaB->name, false)
            ->assertSee('Total (2 TODAs)', false);

        // The combined total (5,5,1 -> 3.7) must be present alongside both rows.
        $this->actingAs($user->fresh())
            ->get('/president/dashboard')
            ->assertSee('3.7', false);
    }

    public function test_president_sidebar_section_shows_only_the_selected_block(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $pres->forceFill(['toda_id' => $toda->id])->save();
        $operator = $this->makeOperator($pres, $toda);
        $operator->setTodaPresidency($toda->id, true);

        $member = $this->makeUser('operator', 'Member Rider');
        $memberOperator = $this->makeOperator($member, $toda);
        $this->rate($memberOperator, $toda, 5);

        // Text that only exists inside the ratings block (the sidebar also has a
        // "My Ratings" link, so the block is tracked by its own container id).
        $ratingsCopy = 'Passenger ratings for your own driving will appear here.';

        // Overview (no section) shows everything.
        $this->actingAs($pres)
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee($ratingsCopy, false)
            ->assertSee('id="membersSection"', false);

        // ?section=ratings shows the ratings block but hides the members block.
        $this->actingAs($pres)->get('/president/dashboard?section=ratings')
            ->assertOk()
            ->assertSee($ratingsCopy, false)
            ->assertDontSee('id="membersSection"', false);

        // ?section=members shows members but hides the ratings block.
        $this->actingAs($pres)->get('/president/dashboard?section=members')
            ->assertOk()
            ->assertSee('id="membersSection"', false)
            ->assertDontSee($ratingsCopy, false);
    }
}
