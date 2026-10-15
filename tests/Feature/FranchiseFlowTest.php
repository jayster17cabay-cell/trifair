<?php

namespace Tests\Feature;

use App\Models\FranchiseTerm;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FranchiseFlowTest extends TestCase
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

    private function makeOperator(string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $this->makeToda()->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makeRating(Operator $operator, int $stars = 5, ?FranchiseTerm $term = null): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'franchise_term_id' => $term ? $term->id : null,
            'rating' => $stars,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    public function test_admin_can_issue_franchise_and_it_shows_in_operators_table()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $res = $this->actingAs($admin)->post(route('superadmin.operators.franchise.issue', $operator), [
            'franchise_no' => 'FR-1234',
            'issued_at' => '2026-01-01',
            'valid_until' => '2027-01-01',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('franchise_terms', [
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-1234',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->get('/superadmin/operators')
            ->assertOk()
            ->assertSee('FR-1234', false);
    }

    public function test_issuing_a_second_franchise_is_blocked()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $this->actingAs($admin)->post(route('superadmin.operators.franchise.issue', $operator), [
            'franchise_no' => 'FR-0001',
        ])->assertRedirect();

        $res = $this->actingAs($admin)->post(route('superadmin.operators.franchise.issue', $operator), [
            'franchise_no' => 'FR-0002',
        ]);

        $res->assertSessionHas('error');
        $this->assertSame(1, FranchiseTerm::where('operator_id', $operator->id)->count());
    }

    public function test_renew_closes_old_term_and_starts_a_new_one_at_zero()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $this->actingAs($admin)->post(route('superadmin.operators.franchise.issue', $operator), [
            'franchise_no' => 'FR-1000',
            'valid_until' => '2026-12-31',
        ])->assertRedirect();

        $operator->unsetRelation('currentTerm');
        $oldTerm = $operator->currentTerm;
        $this->makeRating($operator, 5, $oldTerm);

        $this->actingAs($admin)->post(route('superadmin.operators.franchise.renew', $operator), [
            'franchise_no' => 'FR-1001',
            'valid_until' => '2027-12-31',
        ])->assertRedirect();

        $this->assertSame(FranchiseTerm::STATUS_ENDED, $oldTerm->fresh()->status);

        $operator->unsetRelation('currentTerm');
        $newTerm = $operator->currentTerm;
        $this->assertNotNull($newTerm);
        $this->assertNotSame($oldTerm->id, $newTerm->id);
        $this->assertSame($oldTerm->id, $newTerm->renewed_from_id);
        $this->assertSame('FR-1001', $newTerm->franchise_no);

        $this->assertSame(0, $operator->termCountableRatings($newTerm)->count());
        $this->assertSame(1, $operator->countableRatings()->count());
    }

    public function test_new_rating_is_tagged_to_the_current_term()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $term = FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-2000',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
        ])->assertRedirect();

        $rating = Rating::where('operator_id', $operator->id)->first();
        $this->assertNotNull($rating);
        $this->assertSame($term->id, $rating->franchise_term_id);
    }

    public function test_expired_franchise_blocks_new_ratings()
    {
        $operator = $this->makeOperator();

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-3000',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Franchise Expired', false);

        $this->post(route('rate.submit', $operator->qr_code), [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertOk()->assertSee('Franchise Expired', false);

        $this->assertSame(0, Rating::where('operator_id', $operator->id)->count());
    }

    public function test_tfrb_officer_administers_franchise()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();

        $this->actingAs($officer)->post(route('tfrb-officer.operators.franchise.issue', $operator), [
            'franchise_no' => 'FR-4000',
        ])->assertRedirect();

        $this->assertDatabaseHas('franchise_terms', [
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-4000',
        ]);

        $this->actingAs($officer)->put(route('tfrb-officer.operators.franchise.update', $operator), [
            'franchise_no' => 'FR-4000-REV',
        ])->assertRedirect();

        $this->actingAs($officer)->post(route('tfrb-officer.operators.franchise.renew', $operator), [
            'franchise_no' => 'FR-4001',
        ])->assertRedirect();

        $this->assertSame(2, FranchiseTerm::where('operator_id', $operator->id)->count());
    }

    public function test_operator_pages_render_with_a_franchise_term()
    {
        $operator = $this->makeOperator();
        $user = $operator->user;

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-9000',
            'valid_until' => '2027-06-30',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        foreach (['/operator/dashboard', '/operator/ratings', '/operator/profile'] as $path) {
            $this->actingAs($user)->get($path)
                ->assertOk()
                ->assertSee('FR-9000', false)
                ->assertSee('All Time', false);
        }
    }

    public function test_admin_can_edit_franchise_number_without_resetting_stats()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $term = FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-0001',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);
        $this->makeRating($operator, 5, $term);

        $this->actingAs($admin)->put(route('superadmin.operators.franchise.update', $operator), [
            'franchise_no' => 'MTOP-2026-777',
            'issued_at' => '2026-01-01',
            'valid_until' => '2027-01-01',
        ])->assertRedirect();

        $term->refresh();
        $this->assertSame('MTOP-2026-777', $term->franchise_no);
        $this->assertSame(FranchiseTerm::STATUS_ACTIVE, $term->status);
        $this->assertSame(1, FranchiseTerm::where('operator_id', $operator->id)->count());

        $operator->unsetRelation('currentTerm');
        $this->assertSame(1, $operator->termCountableRatings($operator->currentTerm)->count());

        $this->actingAs($admin)->get('/superadmin/operators')
            ->assertOk()
            ->assertSee('MTOP-2026-777', false);
    }

    public function test_operator_dashboard_shows_all_records_by_default_and_filters_by_period()
    {
        $operator = $this->makeOperator();
        $user = $operator->user;

        $term = FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-NEW',
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        // A rating from a previous year plus one from the current period.
        $old = $this->makeRating($operator, 1, $term);
        $old->forceFill(['created_at' => now()->subYear()])->save();
        $this->makeRating($operator, 5, $term);

        // "All Time" is the default, so a renewal never hides earlier records.
        $this->actingAs($user)->get('/operator/dashboard')
            ->assertOk()
            ->assertSee('Avg Rating · 2 Ratings', false)
            ->assertSee('3.0', false);

        // Narrowing to "This Year" drops the previous-year record.
        $this->actingAs($user)->get('/operator/dashboard?period=year')
            ->assertOk()
            ->assertSee('Avg Rating · 1 Rating', false)
            ->assertSee('5.0', false);
    }

    public function test_sync_command_deactivates_expired_franchise_only()
    {
        $expired = $this->makeOperator('active');
        $valid = $this->makeOperator('active');

        FranchiseTerm::create([
            'operator_id' => $expired->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);
        FranchiseTerm::create([
            'operator_id' => $valid->id,
            'franchise_no' => 'FR-VALID',
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->artisan('franchise:sync-status')->assertExitCode(0);

        $this->assertSame('inactive', $expired->fresh()->status);
        $this->assertSame('active', $valid->fresh()->status);
    }

    public function test_expired_franchise_operator_cannot_log_in()
    {
        $operator = $this->makeOperator('active');
        $user = $operator->user;

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame('inactive', $operator->fresh()->status);
    }

    public function test_renewing_reactivates_an_expired_operator()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('inactive');

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->post(route('superadmin.operators.franchise.renew', $operator), [
            'franchise_no' => 'FR-RENEWED',
            'valid_until' => now()->addYear()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('active', $operator->fresh()->status);
    }

    public function test_expired_franchise_page_shows_even_when_operator_is_inactive()
    {
        $operator = $this->makeOperator('inactive');

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->get(route('rate.operator', $operator->qr_code))
            ->assertOk()
            ->assertSee('Franchise Expired', false);
    }

    public function test_operators_page_renders_for_inactive_expired_operator()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('inactive');

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->get('/superadmin/operators?status=inactive')
            ->assertOk()
            ->assertSee('Inactive', false)
            ->assertSee('Expired', false);
    }

    public function test_dashboard_toda_counts_follow_expiry_and_exclude_president()
    {
        $service = app(\App\Services\AdminDashboardService::class);
        $service->flush();

        $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $expiring = $this->makeOperator('active');
        $expiring->forceFill(['toda_id' => $toda->id])->save();

        $valid = $this->makeOperator('active');
        $valid->forceFill(['toda_id' => $toda->id])->save();

        // A TODA president keeps an Operator row but is not counted as a member
        // (the members modal hides them, so the card must agree).
        $presidentUser = $this->makeUser('operator_president');
        $presidentUser->forceFill(['toda_id' => $toda->id])->save();
        Operator::create([
            'user_id' => $presidentUser->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);

        $term = FranchiseTerm::create([
            'operator_id' => $expiring->id,
            'franchise_no' => 'FR-EXPIRING',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->addDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        // Warm the dashboard cache while both riders still read as active.
        $before = collect($service->stats(['includeOfficers' => true])['todaStats'])
            ->firstWhere('id', $toda->id);
        $this->assertSame(2, (int) $before->operators_count);
        $this->assertSame(2, (int) $before->active_operators_count);

        // The franchise lapses, and the admin list (or daily command) reconciles
        // the status — which must invalidate the stale dashboard cache.
        $term->update(['valid_until' => now()->subDay()->toDateString()]);
        Operator::deactivateExpiredFranchises();

        $after = collect($service->stats(['includeOfficers' => true])['todaStats'])
            ->firstWhere('id', $toda->id);
        $this->assertSame(2, (int) $after->operators_count);
        $this->assertSame(1, (int) $after->active_operators_count);
        $this->assertSame('inactive', $expiring->fresh()->status);
        $this->assertSame('active', $valid->fresh()->status);
    }

    public function test_dashboard_active_count_recovers_after_renewal()
    {
        $service = app(\App\Services\AdminDashboardService::class);
        $service->flush();

        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $operator = $this->makeOperator('active');
        $operator->forceFill(['toda_id' => $toda->id])->save();

        FranchiseTerm::create([
            'operator_id' => $operator->id,
            'franchise_no' => 'FR-EXPIRED',
            'issued_at' => '2024-01-01',
            'valid_until' => now()->subDay()->toDateString(),
            'status' => FranchiseTerm::STATUS_ACTIVE,
        ]);

        Operator::deactivateExpiredFranchises();
        $this->assertSame('inactive', $operator->fresh()->status);

        $this->actingAs($admin)->post(route('superadmin.operators.franchise.renew', $operator), [
            'franchise_no' => 'FR-RENEWED',
            'valid_until' => now()->addYear()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('active', $operator->fresh()->status);

        $card = collect($service->stats(['includeOfficers' => true])['todaStats'])
            ->firstWhere('id', $toda->id);
        $this->assertSame(1, (int) $card->operators_count);
        $this->assertSame(1, (int) $card->active_operators_count);
    }
}
