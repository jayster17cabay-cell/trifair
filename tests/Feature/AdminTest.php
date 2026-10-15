<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\OperatorProof;
use App\Models\OperatorResponse;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTest extends TestCase
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

    private function makeValidComplaint(Operator $operator): Rating
    {
        $rating = $this->makeValidRating($operator, 2);
        $rating->update(['complaint_type' => 'Rude Driver', 'complaint_details' => 'Rude to passenger']);

        RatingProof::create([
            'rating_id' => $rating->id,
            'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $rating;
    }

    public function test_superadmin_pages_return_200()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator);
        $this->makeValidComplaint($operator);
        $this->makeUser('tfrb_officer');
        ActivityLog::create(['user_id' => $admin->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        $this->actingAs($admin);
        foreach ([
            '/superadmin/dashboard',
            '/superadmin/operators',
            '/superadmin/operators/create',
            '/superadmin/operators/' . $operator->id . '/edit',
            '/superadmin/operators/' . $operator->id . '/qrcode',
            '/superadmin/officers',
            '/superadmin/officers/create',
            '/superadmin/complaints',
            '/superadmin/ratings',
            '/superadmin/reports',
            '/superadmin/activity-logs',
            '/superadmin/todas',
            '/superadmin/todas/create',
            '/notifications',
        ] as $path) {
            $this->get($path)->assertStatus(200, 'Failed on: ' . $path);
        }
    }

    public function test_officer_pages_return_200()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator);
        $this->makeValidComplaint($operator);
        ActivityLog::create(['user_id' => $officer->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        $this->actingAs($officer);
        foreach ([
            '/tfrb-officer/dashboard',
            '/tfrb-officer/operators',
            '/tfrb-officer/operators/create',
            '/tfrb-officer/operators/' . $operator->id . '/edit',
            '/tfrb-officer/operators/' . $operator->id . '/qrcode',
            '/tfrb-officer/complaints',
            '/tfrb-officer/ratings',
            '/tfrb-officer/reports',
            '/tfrb-officer/activity-logs',
            '/tfrb-officer/todas',
            '/notifications',
        ] as $path) {
            $this->get($path)->assertStatus(200, 'Failed on: ' . $path);
        }
    }

    public function test_superadmin_dashboard_kpi_links_are_not_double_escaped()
    {
        $admin = $this->makeUser('superadmin');
        $this->makeOperator();
        $this->makeUser('tfrb_officer');

        $response = $this->actingAs($admin)->get('/superadmin/dashboard');

        $response->assertOk();
        $response->assertDontSee('href=&quot;', false);
        foreach (['operators', 'ratings', 'complaints', 'todas', 'officers'] as $section) {
            $response->assertSee('/superadmin/' . $section . '"', false);
        }
        $response->assertSee('data-live="totalOperators"', false);
    }

    public function test_superadmin_can_store_operator()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $this->actingAs($admin)->post('/superadmin/operators', [
            'name' => 'Stored Operator',
            'email' => 'STORED@Example.com',
            'password' => 'password123',
            'phone' => '09171234567',
            'address' => 'QC',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-STORE',
            'body_number' => 'BODY-STORE',
            'motorcycle_model' => 'Honda Wave 125',
            'toda_id' => $toda->id,
        ])->assertRedirect(route('superadmin.operators'));

        $user = User::where('email', 'stored@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('operator', $user->role);
        $this->assertNotNull($user->operator);
        $this->assertEquals('active', $user->operator->status);
        $this->assertFalse(empty($user->operator->qr_code));
    }

    public function test_officer_can_store_operator()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();

        $this->actingAs($officer)->post('/tfrb-officer/operators', [
            'name' => 'Officer Stored',
            'email' => 'officerstored@example.com',
            'password' => 'password123',
            'phone' => '09171234567',
            'plate_number' => 'PLATE-OSTORE',
            'body_number' => 'BODY-OSTORE',
            'toda_id' => $toda->id,
        ])->assertRedirect(route('tfrb-officer.operators'));

        $user = User::where('email', 'officerstored@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('operator', $user->role);
    }

    public function test_update_operator_changes_data()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $toda = $this->makeToda();

        $this->actingAs($admin)->put('/superadmin/operators/' . $operator->id, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '09170000000',
            'plate_number' => $operator->plate_number,
            'body_number' => $operator->body_number,
            'status' => 'active',
            'toda_id' => $toda->id,
        ])->assertRedirect(route('superadmin.operators'));

        $this->assertEquals('Updated Name', $operator->fresh()->user->name);
        $this->assertEquals('active', $operator->fresh()->status);
    }

    public function test_destroy_operator_without_ratings_deletes()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $userId = $operator->user_id;

        $this->actingAs($admin)->delete('/superadmin/operators/' . $operator->id)
            ->assertRedirect(route('superadmin.operators'));

        $this->assertNull(Operator::find($operator->id));
        $this->assertNull(User::find($userId));
    }

    public function test_destroy_operator_with_ratings_is_blocked()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator);

        $this->actingAs($admin)->delete('/superadmin/operators/' . $operator->id)
            ->assertSessionHas('error');

        $this->assertNotNull(Operator::find($operator->id));
    }

    public function test_approve_pending_operator()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('pending');

        $this->actingAs($admin)->patch('/superadmin/operators/' . $operator->id . '/approve')
            ->assertRedirect();

        $this->assertEquals('active', $operator->fresh()->status);
        $this->assertTrue((bool) $operator->fresh()->user->is_active);
    }

    public function test_reject_pending_operator_deletes()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('pending');
        $userId = $operator->user_id;

        $this->actingAs($admin)->patch('/superadmin/operators/' . $operator->id . '/reject')
            ->assertRedirect();

        $this->assertNull(Operator::find($operator->id));
        $this->assertNull(User::find($userId));
    }

    public function test_marking_complaint_accepted_counts_toward_rating()
    {
        $admin = $this->makeUser('superadmin');
        $rating = $this->makeValidComplaint($this->makeOperator());

        $this->actingAs($admin)->patch('/superadmin/complaints/' . $rating->id . '/accept')
            ->assertRedirect();

        $fresh = $rating->fresh();
        $this->assertTrue((bool) $fresh->is_reviewed);
        $this->assertTrue($fresh->is_accepted);
    }

    public function test_marking_complaint_rejected_does_not_count()
    {
        $admin = $this->makeUser('superadmin');
        $rating = $this->makeValidComplaint($this->makeOperator());

        $this->actingAs($admin)->patch('/superadmin/complaints/' . $rating->id . '/reject')
            ->assertRedirect();

        $fresh = $rating->fresh();
        $this->assertTrue((bool) $fresh->is_reviewed);
        $this->assertFalse($fresh->is_accepted);
    }

    public function test_complaint_review_can_be_reset_to_pending()
    {
        $admin = $this->makeUser('superadmin');
        $rating = $this->makeValidComplaint($this->makeOperator());
        $rating->update(['is_reviewed' => true, 'is_accepted' => false]);

        $this->actingAs($admin)->patch('/superadmin/complaints/' . $rating->id . '/reset')
            ->assertRedirect();

        $fresh = $rating->fresh();
        $this->assertFalse((bool) $fresh->is_reviewed);
        $this->assertNull($fresh->is_accepted);
    }

    public function test_superadmin_can_bulk_review_complaints()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $a = $this->makeValidComplaint($operator);
        $b = $this->makeValidComplaint($operator);

        $this->actingAs($admin)->post('/superadmin/complaints/bulk-review', [
            'ids' => json_encode([$a->id, $b->id]),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue((bool) $a->fresh()->is_reviewed);
        $this->assertTrue($a->fresh()->is_accepted);
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
        $this->assertTrue($b->fresh()->is_accepted);
    }

    public function test_officer_can_bulk_review_complaints()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $a = $this->makeValidComplaint($operator);
        $b = $this->makeValidComplaint($operator);

        $this->actingAs($officer)->post('/tfrb-officer/complaints/bulk-review', [
            'ids' => json_encode([$a->id, $b->id]),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue((bool) $a->fresh()->is_reviewed);
        $this->assertTrue($a->fresh()->is_accepted);
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
        $this->assertTrue($b->fresh()->is_accepted);
    }

    public function test_operators_page_renders_compact_table_and_modal()
    {
        $admin = $this->makeUser('superadmin');
        $this->makeOperator();

        $res = $this->actingAs($admin)->get('/superadmin/operators');

        $res->assertOk();
        $res->assertSee('tw-table-scroll-wrap', false);
        $res->assertSee('tw-thead-sticky', false);
        $res->assertSee('data-operator-view', false);
        $res->assertSee('operatorDetailsModal', false);
        $res->assertSee('bi-eye', false);
        $res->assertSee('data-tw-modal-close', false);
        $res->assertSee('View QR Code', false);
        $res->assertDontSee('License #', false);
        $res->assertSee('even:bg-slate-50/60', false);
        $res->assertDontSee('<th class="tw-th">#</th>', false);
        $res->assertDontSee('<th class="tw-th">Plate #</th>', false);
        $res->assertDontSee('<th class="tw-th">Body #</th>', false);
        $res->assertDontSee('<th class="tw-th text-center">QR</th>', false);
    }

    public function test_complaints_page_renders_collapsible_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        $res = $this->actingAs($admin)->get('/superadmin/complaints');

        $res->assertOk();
        $res->assertSee('data-complaint-card', false);
        $res->assertSee('data-complaint-toggle', false);
        $res->assertSee('id="complaint_filter"', false);
        $res->assertSee('id="complaint_rating"', false);
        $res->assertDontSee('data-complaint-select-all', false);
        $res->assertDontSee('complaintBulkReviewForm', false);
        $res->assertSee('border-l-4', false);
    }

    public function test_ratings_page_renders_collapsible_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator, 5);
        $this->makeValidRating($operator, 1);

        $res = $this->actingAs($admin)->get('/superadmin/ratings');

        $res->assertOk();
        $res->assertSee('data-rating-card', false);
        $res->assertSee('data-rating-toggle', false);
        $res->assertSee('data-rating-check', false);
        $res->assertSee('data-rating-select-all', false);
        $res->assertSee('data-rating-bulk-review', false);
        $res->assertSee('ratingBulkReviewForm', false);
        $res->assertSee('border-l-4', false);
        $res->assertSee('border-l-emerald-500', false);
    }

    public function test_superadmin_can_bulk_review_ratings()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $a = $this->makeValidRating($operator, 4);
        $b = $this->makeValidRating($operator, 5);

        $this->actingAs($admin)->post('/superadmin/ratings/bulk-review', [
            'ids' => json_encode([$a->id, $b->id]),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue((bool) $a->fresh()->is_reviewed);
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
    }

    public function test_ratings_bulk_review_ignores_complaints()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator);

        $this->actingAs($admin)->post('/superadmin/ratings/bulk-review', [
            'ids' => json_encode([$complaint->id]),
        ])->assertRedirect();

        $this->assertFalse((bool) $complaint->fresh()->is_reviewed);
        $this->assertNull($complaint->fresh()->is_accepted);
    }

    public function test_destroying_a_complaint_removes_operator_proofs()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator);

        OperatorProof::create([
            'rating_id' => $complaint->id,
            'file_path' => 'operator-proofs/' . $operator->qr_code . '/response.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'response.jpg',
        ]);

        $this->actingAs($admin)
            ->delete('/superadmin/complaints/' . $complaint->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('ratings', ['id' => $complaint->id]);
        $this->assertDatabaseMissing('operator_response_proofs', ['rating_id' => $complaint->id]);
        $this->assertDatabaseMissing('rating_proofs', ['rating_id' => $complaint->id]);
    }

    public function test_officer_can_bulk_review_ratings()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $a = $this->makeValidRating($operator, 4);
        $b = $this->makeValidRating($operator, 5);

        $this->actingAs($officer)->post('/tfrb-officer/ratings/bulk-review', [
            'ids' => json_encode([$a->id, $b->id]),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue((bool) $a->fresh()->is_reviewed);
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
    }

    public function test_ratings_bulk_review_without_ids_errors()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidRating($operator, 5);

        $this->actingAs($admin)->post('/superadmin/ratings/bulk-review', ['ids' => json_encode([])])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertFalse((bool) $rating->fresh()->is_reviewed);
    }

    public function test_bulk_review_without_ids_errors()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidComplaint($operator);

        $this->actingAs($admin)->post('/superadmin/complaints/bulk-review', ['ids' => json_encode([])])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertFalse((bool) $rating->fresh()->is_reviewed);
    }

    public function test_reports_page_renders_drawer_and_star_ratings()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        for ($i = 1; $i <= 6; $i++) {
            $this->makeValidRating($operator, $i <= 2 ? 2 : ($i <= 4 ? 3 : 5));
        }

        $res = $this->actingAs($admin)->get('/superadmin/reports');

        $res->assertOk();
        $res->assertSee('data-open-trips', false);
        $res->assertSee('data-row-chevron', false);
        $res->assertSee('data-trip-drawer', false);
        $res->assertSee('tw-drawer', false);
        $res->assertSee('tw-drawer-overlay', false);
        $res->assertSee('bi-star-fill', false);
        $res->assertSee('trips', false);
        $res->assertSee('data-trips-url', false);
    }

    public function test_reports_period_filter_scopes_records_and_average()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $this->makeValidRating($operator, 5);
        $lastYear = $this->makeValidRating($operator, 1);
        $lastYear->forceFill(['created_at' => now()->subYear()])->save();

        // All Time (default) keeps every record: avg of 5 and 1 = 3.0, 2 trips.
        $all = $this->actingAs($admin)->get('/superadmin/reports')->assertOk()->getContent();
        $this->assertStringContainsString('2 trips', $all);
        $this->assertStringContainsString('3.0', $all);

        // This Year drops the previous-year record: avg 5.0, 1 trip.
        $year = $this->actingAs($admin)->get('/superadmin/reports?period=year')->assertOk()->getContent();
        $this->assertStringContainsString('1 trip', $year);
        $this->assertStringContainsString('5.0', $year);
    }

    public function test_reports_export_honors_period_filter()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $this->makeValidRating($operator, 5);
        $lastYear = $this->makeValidRating($operator, 1);
        $lastYear->forceFill(['created_at' => now()->subYear()])->save();

        // Export with This Year carries only this year's average.
        $year = $this->actingAs($admin)->get('/superadmin/reports/export?format=csv&period=year');
        $year->assertOk();
        $this->assertStringContainsString($operator->user->name, $year->getContent());
        $this->assertStringContainsString('5.00', $year->getContent());

        // All Time export includes the previous-year record in the average.
        $all = $this->actingAs($admin)->get('/superadmin/reports/export?format=csv');
        $all->assertOk();
        $this->assertStringContainsString('3.00', $all->getContent());
    }

    public function test_reports_export_scope_by_all_operator_or_toda()
    {
        $admin = $this->makeUser('superadmin');
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $opA = $this->makeOperator('active', $todaA);
        $opA->user->forceFill(['name' => 'Alpha Driver'])->save();
        $opB = $this->makeOperator('active', $todaB);
        $opB->user->forceFill(['name' => 'Beta Driver'])->save();

        $this->makeValidRating($opA, 5);
        $this->makeValidRating($opB, 1);

        // All operators: both are exported.
        $all = $this->actingAs($admin)->get('/superadmin/reports/export?format=csv');
        $all->assertOk();
        $this->assertStringContainsString('Alpha Driver', $all->getContent());
        $this->assertStringContainsString('Beta Driver', $all->getContent());

        // One TODA: only that TODA's operator.
        $toda = $this->actingAs($admin)->get('/superadmin/reports/export?format=csv&toda_id=' . $todaA->id);
        $toda->assertOk();
        $this->assertStringContainsString('Alpha Driver', $toda->getContent());
        $this->assertStringNotContainsString('Beta Driver', $toda->getContent());

        // One operator: only that operator.
        $one = $this->actingAs($admin)->get('/superadmin/reports/export?format=csv&operator_id=' . $opB->id);
        $one->assertOk();
        $this->assertStringContainsString('Beta Driver', $one->getContent());
        $this->assertStringNotContainsString('Alpha Driver', $one->getContent());
    }

    public function test_reports_trips_endpoint_renders_cards_and_show_all()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        for ($i = 1; $i <= 6; $i++) {
            $this->makeValidRating($operator, $i === 1 ? 1 : 5);
        }

        $res = $this->actingAs($admin)->get('/superadmin/reports/operators/' . $operator->id . '/trips');

        $res->assertOk();
        $html = $res->json('html');
        $this->assertIsString($html);
        $this->assertStringContainsString('data-trip-show-all', $html);
        $this->assertStringContainsString('data-trip-more', $html);
        $this->assertStringContainsString('bg-red-50/60', $html);
        $this->assertStringContainsString('bi-star-fill', $html);
    }

    public function test_reports_trips_endpoint_matches_row_counts_for_period_and_complaints()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        // Plain rating: always counts.
        $this->makeValidRating($operator, 5);

        // Pending complaint (not accepted yet): must NOT count (same rule as the row).
        $pending = $this->makeValidRating($operator, 2);
        $pending->update(['complaint_type' => 'Rude Driver']);

        // Accepted complaint: counts.
        $accepted = $this->makeValidRating($operator, 2);
        $accepted->update(['complaint_type' => 'Rude Driver', 'is_accepted' => true]);

        // Older plain rating: counts overall, but must drop out of a "today" period.
        $old = $this->makeValidRating($operator, 4);
        $old->created_at = now()->subMonths(2);
        $old->save();

        $all = $this->actingAs($admin)
            ->get('/superadmin/reports/operators/' . $operator->id . '/trips')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('3 trips', $all);

        $today = $this->actingAs($admin)
            ->get('/superadmin/reports/operators/' . $operator->id . '/trips?date_from=' . now()->toDateString())
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('2 trips', $today);
    }

    public function test_superadmin_can_delete_complaint()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidComplaint($operator);
        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'complaint',
            'title' => 'New Complaint',
            'message' => 'Test complaint',
        ]);

        $this->actingAs($admin)->delete('/superadmin/complaints/' . $rating->id)
            ->assertRedirect();

        $this->assertNull(Rating::find($rating->id));
        $this->assertEquals(0, Notification::where('rating_id', $rating->id)->count());
    }

    public function test_notifications_page_renders_grouped_compact_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidRating($operator);
        $rating->update(['passenger_contact' => '09170001111']);

        $today = Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'complaint',
            'title' => 'New Complaint Report',
            'message' => 'Operator received a 2-star rating (reckless driving).',
            'is_read' => false,
        ]);
        $yesterday = Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'message' => 'Operator received a 5-star rating from a passenger.',
            'is_read' => true,
        ]);
        $yesterday->created_at = now()->subDay();
        $yesterday->save();
        $earlier = Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'operator_response',
            'title' => 'Operator Responded',
            'message' => 'Operator responded to a rating.',
            'is_read' => false,
        ]);
        $earlier->created_at = now()->subDays(10);
        $earlier->save();

        $response = $this->actingAs($admin)->get('/notifications');

        $response->assertOk();
        $response->assertSee('Today', false);
        $response->assertSee('Yesterday', false);
        $response->assertSee('Earlier', false);
        $response->assertSee('data-notification-card', false);
        $response->assertSee('data-notification-toggle', false);
        $response->assertSee('data-notification-details', false);
        $response->assertSee('data-notification-dot', false);
        $response->assertSee('border-l-amber-400', false);
        $response->assertSee('border-l-emerald-500', false);
        $response->assertSee('border-l-blue-500', false);
        $response->assertSee('Operator responded to a rating.', false);
        $response->assertDontSee('View Details');
        $response->assertSee('Mark All as Read');
    }

    public function test_notifications_show_reference_number_and_driver_response()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator);
        OperatorResponse::create([
            'rating_id' => $complaint->id,
            'message' => 'Hindi ako nag-counterflow, may dashcam po ako.',
        ]);
        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $complaint->id,
            'type' => 'operator_response',
            'title' => 'Operator Responded',
            'message' => 'Operator responded to a rating.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($admin)->get('/notifications');

        $response->assertOk();
        $response->assertSee($complaint->reference_number, false);
        $response->assertSee("Driver's Response", false);
        $response->assertSee('Hindi ako nag-counterflow', false);
    }

    public function test_positive_ratings_have_no_reference_number()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator);
        $positive = $this->makeValidRating($operator, 5);

        $this->assertNotNull($complaint->reference_number);
        $this->assertNull($positive->reference_number);

        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $positive->id,
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'message' => 'Operator received a 5-star rating from a passenger.',
            'is_read' => false,
        ]);

        $html = $this->actingAs($admin)->get('/notifications')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/TFR-\d{4}-\d{4}/', $html);
    }

    public function test_operator_response_notification_opens_the_complaint()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $complaint = $this->makeValidComplaint($operator);
        $notification = Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $complaint->id,
            'type' => 'operator_response',
            'title' => 'Operator Responded',
            'message' => 'Operator responded to a rating.',
            'is_read' => false,
        ]);

        $this->actingAs($admin)
            ->get('/notifications/' . $notification->id . '/read')
            ->assertRedirect(route('superadmin.complaints', [
                'filter' => 'all',
                'search' => $complaint->reference_number,
            ]));
    }

    public function test_notification_ajax_read_marks_read_and_returns_unread_count()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidRating($operator);

        $notification = Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'message' => 'Operator received a 5-star rating from a passenger.',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'complaint',
            'title' => 'New Complaint Report',
            'message' => 'Operator received a 2-star rating.',
            'is_read' => false,
        ]);

        $this->actingAs($admin)->postJson('/notifications/' . $notification->id . '/read')
            ->assertOk()
            ->assertJson(['ok' => true, 'unread_count' => 1]);

        $this->assertTrue((bool) $notification->fresh()->is_read);
    }

    public function test_notification_ajax_read_rejects_other_users_notification()
    {
        $admin = $this->makeUser('superadmin');
        $other = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $rating = $this->makeValidRating($operator);

        $notification = Notification::create([
            'user_id' => $other->id,
            'rating_id' => $rating->id,
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'message' => 'Operator received a 5-star rating from a passenger.',
            'is_read' => false,
        ]);

        $this->actingAs($admin)->postJson('/notifications/' . $notification->id . '/read')
            ->assertForbidden();

        $this->assertFalse((bool) $notification->fresh()->is_read);
    }

    public function test_notifications_index_returns_json_for_live_polling()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = $this->makeValidRating($operator);

        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'message' => 'Operator received a 5-star rating from a passenger.',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $admin->id,
            'rating_id' => $rating->id,
            'type' => 'complaint',
            'title' => 'New Complaint Report',
            'message' => 'Operator received a 2-star rating.',
            'is_read' => true,
        ]);

        $response = $this->actingAs($admin)->getJson('/notifications');

        $response->assertOk();
        $response->assertJsonStructure(['html', 'signature', 'counts', 'unreadCount', 'hasItems']);
        $response->assertJson([
            'unreadCount' => 1,
            'hasItems' => true,
        ]);
        $response->assertJsonPath('counts.all', 2);
        $response->assertJsonPath('counts.unread', 1);
        $response->assertJsonPath('counts.new_rating', 1);
        $this->assertStringContainsString('data-notification-card', $response->json('html'));
        $this->assertStringContainsString('New Complaint Report', $response->json('html'));

        // Filtered view keeps its signature/JSON shape
        $filtered = $this->actingAs($admin)->getJson('/notifications?type=unread');
        $filtered->assertOk();
        $this->assertStringContainsString('data-notification-card', $filtered->json('html'));
    }

    public function test_todas_index_renders_new_table_with_badges_and_clickable_rows()
    {
        $admin = $this->makeUser('superadmin');
        $withMembers = $this->makeToda();
        $this->makeOperator('active', $withMembers);
        $empty = $this->makeToda();

        $this->actingAs($admin)->get('/superadmin/todas')
            ->assertOk()
            ->assertSee('Search TODA by name or area')
            ->assertSee($withMembers->name)
            ->assertSee($empty->name)
            ->assertSee('1 total')
            ->assertSee('1 active')
            ->assertSee('No members')
            ->assertSee('showTodaMembers', false);
    }

    public function test_todas_index_search_filters_by_name_or_area()
    {
        $admin = $this->makeUser('superadmin');
        $alpha = Toda::create(['name' => 'Alpha TODA', 'area' => 'Quezon City', 'is_active' => true]);
        $beta = Toda::create(['name' => 'Beta TODA', 'area' => 'Marikina', 'is_active' => true]);

        $byName = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/superadmin/todas?search=Alpha');
        $byName->assertOk()
            ->assertJsonStructure(['html', 'pagination']);
        $this->assertStringContainsString('Alpha TODA', $byName->json('html'));
        $this->assertStringNotContainsString('Beta TODA', $byName->json('html'));

        $byArea = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/superadmin/todas?search=Marikina');
        $this->assertStringContainsString('Beta TODA', $byArea->json('html'));
        $this->assertStringNotContainsString('Alpha TODA', $byArea->json('html'));
    }

    public function test_todas_members_modal_renders_member_rows_with_actions()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeOperator('active', $toda);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/toda/' . $toda->id . '/members');

        $response->assertOk()->assertJsonPath('count', 1);
        $html = $response->json('html');
        $this->assertStringContainsString($operator->user->name, $html);
        $this->assertStringContainsString($operator->plate_number, $html);
        $this->assertStringContainsString($operator->body_number, $html);
        $this->assertStringContainsString('Active', $html);
        $this->assertStringContainsString('/superadmin/operators/' . $operator->id . '/edit', $html);
        $this->assertStringContainsString('/superadmin/operators/' . $operator->id . '/archive', $html);
    }

    public function test_todas_members_modal_empty_state()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/toda/' . $toda->id . '/members');

        $response->assertOk()->assertJsonPath('count', 0);
        $this->assertStringContainsString('No members yet', $response->json('html'));
    }

    public function test_operator_create_preselects_toda_from_query()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $this->actingAs($admin)->get('/superadmin/operators/create?toda_id=' . $toda->id)
            ->assertOk()
            ->assertSee('value="' . $toda->id . '" selected', false);
    }
}
