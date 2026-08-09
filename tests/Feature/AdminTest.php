<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Operator;
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

    private function makeOperator(string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $this->makeToda()->id,
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

    private function makeValidComplaint(Operator $operator): Rating
    {
        $rating = $this->makeValidRating($operator, 2);

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
            '/superadmin/invalid-ratings',
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
            '/tfrb-officer/invalid-ratings',
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
            'license_number' => 'LIC-STORE',
            'address' => 'QC',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-STORE',
            'body_number' => 'BODY-STORE',
            'tricycle_color' => 'Red',
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

    public function test_mark_reviewed_marks_rating()
    {
        $admin = $this->makeUser('superadmin');
        $rating = $this->makeValidComplaint($this->makeOperator());

        $this->actingAs($admin)->patch('/superadmin/complaints/' . $rating->id . '/review')
            ->assertRedirect();

        $this->assertTrue((bool) $rating->fresh()->is_reviewed);
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
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
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
        $this->assertTrue((bool) $b->fresh()->is_reviewed);
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
        $res->assertSee('data-complaint-check', false);
        $res->assertSee('data-complaint-select-all', false);
        $res->assertSee('data-bulk-review', false);
        $res->assertSee('bulkReviewForm', false);
        $res->assertSee('border-l-4', false);
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

    public function test_restore_invalid_rating_reapplies_validity()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
            'is_valid' => false,
            'is_auto' => true,
        ]);

        $this->actingAs($admin)->patch('/superadmin/ratings/' . $rating->id . '/restore')
            ->assertRedirect();

        $this->assertTrue((bool) $rating->fresh()->is_valid);
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
}
