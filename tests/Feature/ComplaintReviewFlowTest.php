<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the complaint accept/reject review flow and, most importantly, the
 * rule that only accepted 1-2 star complaints count towards an operator's
 * star average. Pending and rejected complaints must never drag it down.
 */
class ComplaintReviewFlowTest extends TestCase
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

    private function makeOperator(string $status = 'active', ?Toda $toda = null): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => ($toda ?? $this->makeToda())->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makeValidRating(Operator $operator, int $stars = 5): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'rating' => $stars,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    private function makeValidComplaint(Operator $operator, int $stars = 1): Rating
    {
        $rating = $this->makeValidRating($operator, $stars);
        $rating->update(['complaint_type' => 'Rude Driver', 'complaint_details' => 'Rude to passenger']);

        RatingProof::create([
            'rating_id' => $rating->id,
            'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $rating;
    }

    public function test_pending_complaint_does_not_affect_operator_average()
    {
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $this->makeValidComplaint($operator, 1);

        $this->assertEqualsWithDelta(5.0, (float) $operator->averageRating(), 0.001);
        $this->assertSame(1, $operator->totalRatings());
    }

    public function test_accepted_complaint_counts_toward_operator_average()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/accept')
            ->assertRedirect();

        $this->assertEqualsWithDelta(3.0, (float) $operator->averageRating(), 0.001);
        $this->assertSame(2, $operator->totalRatings());
    }

    public function test_rejected_complaint_does_not_count_toward_operator_average()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/reject')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertFalse($fresh->is_accepted);
        $this->assertFalse((bool) $fresh->is_solved);

        $this->assertEqualsWithDelta(5.0, (float) $operator->averageRating(), 0.001);
        $this->assertSame(1, $operator->totalRatings());
    }

    public function test_solving_complaint_implies_acceptance_and_counts()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/solve')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertTrue($fresh->is_accepted);
        $this->assertTrue($fresh->is_solved);

        $this->assertEqualsWithDelta(3.0, (float) $operator->averageRating(), 0.001);
        $this->assertSame(2, $operator->totalRatings());
    }

    public function test_undoing_review_returns_complaint_to_pending_queue()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);
        $complaint->update(['is_reviewed' => true, 'is_accepted' => false]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/reset')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertFalse((bool) $fresh->is_reviewed);
        $this->assertNull($fresh->is_accepted);
    }

    public function test_admin_dashboard_average_excludes_unaccepted_complaints()
    {
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $complaint = $this->makeValidComplaint($operator, 1);

        Cache::flush();
        $stats = app(AdminDashboardService::class)->stats(['includeOfficers' => true]);
        $this->assertEqualsWithDelta(5.0, (float) $stats['averageRating'], 0.001);
        $this->assertSame(1, $stats['totalRatings']);

        // Accepting the complaint pulls the shared average down to 3.0.
        $complaint->update(['is_reviewed' => true, 'is_accepted' => true]);
        Cache::flush();
        $stats = app(AdminDashboardService::class)->stats(['includeOfficers' => true]);
        $this->assertEqualsWithDelta(3.0, (float) $stats['averageRating'], 0.001);
        $this->assertSame(2, $stats['totalRatings']);
    }

    public function test_complaint_status_filters_split_the_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $pending = $this->makeValidComplaint($operator, 1);
        $accepted = $this->makeValidComplaint($operator, 1);
        $accepted->update(['is_reviewed' => true, 'is_accepted' => true]);
        $rejected = $this->makeValidComplaint($operator, 1);
        $rejected->update(['is_reviewed' => true, 'is_accepted' => false]);
        $solved = $this->makeValidComplaint($operator, 1);
        $solved->update(['is_reviewed' => true, 'is_accepted' => true, 'is_solved' => true, 'solved_at' => now()]);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=pending')
            ->assertOk()
            ->assertSee($pending->reference_number, false)
            ->assertDontSee($accepted->reference_number, false)
            ->assertDontSee($rejected->reference_number, false)
            ->assertDontSee($solved->reference_number, false);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=accepted')
            ->assertOk()
            ->assertSee($accepted->reference_number, false)
            ->assertDontSee($pending->reference_number, false)
            ->assertDontSee($rejected->reference_number, false)
            ->assertDontSee($solved->reference_number, false);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=rejected')
            ->assertOk()
            ->assertSee($rejected->reference_number, false)
            ->assertDontSee($pending->reference_number, false)
            ->assertDontSee($accepted->reference_number, false);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=solved')
            ->assertOk()
            ->assertSee($solved->reference_number, false)
            ->assertDontSee($accepted->reference_number, false);
    }

    public function test_status_label_priority_is_solved_then_accepted_then_rejected()
    {
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->assertSame('Pending', $complaint->status_label);

        $complaint->update(['is_accepted' => true]);
        $this->assertSame('Accepted', $complaint->fresh()->status_label);

        $complaint->update(['is_accepted' => false]);
        $this->assertSame('Rejected', $complaint->fresh()->status_label);

        $complaint->update(['is_accepted' => true, 'is_solved' => true]);
        $this->assertSame('Solved', $complaint->fresh()->status_label);
    }

    public function test_passenger_dashboard_shows_accepted_and_rejected_badges()
    {
        $operator = $this->makeOperator();
        $passenger = $this->makeUser('passenger');

        $accepted = $this->makeValidComplaint($operator, 1);
        $accepted->update(['passenger_user_id' => $passenger->id, 'is_reviewed' => true, 'is_accepted' => true]);
        $rejected = $this->makeValidComplaint($operator, 1);
        $rejected->update(['passenger_user_id' => $passenger->id, 'is_reviewed' => true, 'is_accepted' => false]);

        $this->actingAs($passenger)->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Accepted')
            ->assertSee('Rejected');
    }
}
