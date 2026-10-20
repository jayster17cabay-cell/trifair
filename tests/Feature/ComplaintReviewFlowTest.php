<?php

namespace Tests\Feature;

use App\Mail\ComplaintStatus;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\ResolutionProof;
use App\Models\Toda;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

    public function test_review_actions_notify_the_other_officers_but_not_the_actor()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();

        $accepted = $this->makeValidComplaint($operator, 1);
        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $accepted->id . '/accept')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $accepted->id,
            'type' => 'complaint',
            'title' => 'Complaint Accepted',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $admin->id,
            'title' => 'Complaint Accepted',
        ]);

        $rejected = $this->makeValidComplaint($operator, 1);
        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $rejected->id . '/reject')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $rejected->id,
            'type' => 'complaint',
            'title' => 'Complaint Rejected',
        ]);

        $solved = $this->makeValidComplaint($operator, 1);
        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $solved->id . '/solve')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'rating_id' => $solved->id,
            'type' => 'complaint',
            'title' => 'Complaint Solved',
        ]);
    }

    public function test_re_reviewing_a_complaint_does_not_duplicate_officer_notices()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)->patch('/superadmin/complaints/' . $complaint->id . '/accept')->assertRedirect();
        $this->actingAs($admin)->patch('/superadmin/complaints/' . $complaint->id . '/accept')->assertRedirect();

        $this->assertSame(1, \App\Models\Notification::where('user_id', $officer->id)
            ->where('title', 'Complaint Accepted')
            ->count());
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

    public function test_rejecting_with_a_note_shows_the_reason_to_the_passenger()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $passenger = $this->makeUser('passenger');
        $complaint = $this->makeValidComplaint($operator, 1);
        $complaint->update(['passenger_user_id' => $passenger->id]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/reject', [
                'resolution_note' => 'There is not enough evidence to prove the complaint.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertSame('There is not enough evidence to prove the complaint.', $fresh->resolution_note);

        $this->actingAs($passenger)->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Reason for rejection')
            ->assertSee('There is not enough evidence to prove the complaint.');
    }

    public function test_solving_with_a_note_stores_and_displays_the_resolution()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $passenger = $this->makeUser('passenger');
        $complaint = $this->makeValidComplaint($operator, 1);
        $complaint->update(['passenger_user_id' => $passenger->id]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/solve', [
                'resolution_note' => 'The driver was contacted and issued an apology.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertSame('The driver was contacted and issued an apology.', $fresh->resolution_note);
        $this->assertTrue($fresh->is_solved);

        // A stored proof file is surfaced to the passenger as resolution proof.
        ResolutionProof::create([
            'rating_id' => $complaint->id,
            'file_path' => 'resolution-proofs/' . $complaint->id . '/resolved.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'resolved.jpg',
        ]);

        $this->actingAs($passenger)->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Resolution details')
            ->assertSee('The driver was contacted and issued an apology.')
            ->assertSee('resolved.jpg');
    }

    public function test_solved_complaint_email_embeds_the_resolution_photo()
    {
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);
        $complaint->update([
            'is_reviewed' => true,
            'is_accepted' => true,
            'is_solved' => true,
            'solved_at' => now(),
        ]);

        ResolutionProof::create([
            'rating_id' => $complaint->id,
            'file_path' => 'resolution-proofs/' . $complaint->id . '/resolved.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'resolved.jpg',
        ]);

        $html = (new ComplaintStatus($complaint->fresh(), 'solved'))->render();

        // The officer's photo is embedded directly through a signed URL so the
        // passenger can view it straight from the email without signing in.
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('file-storage/resolution-proofs', $html);
        $this->assertStringContainsString('resolved.jpg', $html);
        $this->assertStringNotContainsString('Sign in', $html);
    }

    public function test_reset_and_reopen_clear_the_resolution_details()
    {
        Http::fake();

        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $solved = $this->makeValidComplaint($operator, 1);
        $solved->update([
            'is_reviewed' => true,
            'is_accepted' => true,
            'is_solved' => true,
            'solved_at' => now(),
            'resolution_note' => 'Resolved already.',
        ]);
        ResolutionProof::create([
            'rating_id' => $solved->id,
            'file_path' => 'resolution-proofs/' . $solved->id . '/resolved.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'resolved.jpg',
        ]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $solved->id . '/reopen')
            ->assertRedirect();

        $fresh = $solved->fresh();
        $this->assertNull($fresh->resolution_note);
        $this->assertSame(0, ResolutionProof::where('rating_id', $solved->id)->count());

        $rejected = $this->makeValidComplaint($operator, 1);
        $rejected->update(['is_reviewed' => true, 'is_accepted' => false, 'resolution_note' => 'Not a valid complaint.']);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $rejected->id . '/reset')
            ->assertRedirect();

        $this->assertNull($rejected->fresh()->resolution_note);
    }

    public function test_rejecting_records_reviewer_and_timestamp()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/reject', [
                'resolution_note' => 'The complaint could not be proven.',
            ])
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertSame($admin->name, $fresh->reviewed_by_name);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertNull($fresh->solved_at);
        $this->assertSame($fresh->rejected_at->timestamp, $fresh->resolution_at->timestamp);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=rejected')
            ->assertOk()
            ->assertSee('Reviewed by ' . $admin->name)
            ->assertSee($fresh->rejected_at->format('M j, Y g:i A'), false);
    }

    public function test_solving_records_reviewer_and_timestamp()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/solve')
            ->assertRedirect();

        $fresh = $complaint->fresh();
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertSame($admin->name, $fresh->reviewed_by_name);
        $this->assertNotNull($fresh->solved_at);
        $this->assertNull($fresh->rejected_at);
        $this->assertSame($fresh->solved_at->timestamp, $fresh->resolution_at->timestamp);
    }

    public function test_reset_and_reopen_clear_reviewer_and_timestamps()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $rejected = $this->makeValidComplaint($operator, 1);
        $rejected->update([
            'is_reviewed' => true,
            'is_accepted' => false,
            'rejected_at' => now(),
            'reviewed_by' => $admin->id,
            'resolution_note' => 'Not valid.',
        ]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $rejected->id . '/reset')
            ->assertRedirect();

        $freshRejected = $rejected->fresh();
        $this->assertNull($freshRejected->rejected_at);
        $this->assertNull($freshRejected->reviewed_by);

        $solved = $this->makeValidComplaint($operator, 1);
        $solved->update([
            'is_reviewed' => true,
            'is_accepted' => true,
            'is_solved' => true,
            'solved_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $solved->id . '/reopen')
            ->assertRedirect();

        $freshSolved = $solved->fresh();
        $this->assertNull($freshSolved->solved_at);
        $this->assertNull($freshSolved->reviewed_by);
    }

    public function test_landing_page_uses_live_system_overall_rating()
    {
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 4);
        $this->makeValidRating($operator, 5);
        $rejected = $this->makeValidComplaint($operator, 1);
        $rejected->update(['is_reviewed' => true, 'is_accepted' => false]);

        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-landing-stat="average"', false)
            ->assertSee('>4.5<', false)
            ->assertSee('data-landing-stat="total"', false)
            ->assertSee('data-landing-stars', false);

        $stats = $this->getJson('/landing-stats')->assertOk()->json();
        $this->assertEqualsWithDelta(4.5, (float) $stats['average'], 0.001);
        $this->assertSame(2, $stats['total']);
    }
}
