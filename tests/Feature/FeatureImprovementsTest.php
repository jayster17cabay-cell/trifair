<?php

namespace Tests\Feature;

use App\Mail\OperatorCredentials;
use App\Models\ActivityLog;
use App\Models\Operator;
use App\Models\OperatorProof;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureImprovementsTest extends TestCase
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

    private function makeValidComplaint(Operator $operator): Rating
    {
        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 2,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
            'complaint_type' => 'Rude Driver',
            'complaint_details' => 'Rude to passenger',
        ]);

        RatingProof::create([
            'rating_id' => $rating->id,
            'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $rating;
    }

    private function makeValidRating(Operator $operator, int $stars = 4): Rating
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

    public function test_forgot_password_page_is_public()
    {
        $this->get('/password/reset')->assertOk()->assertSee('Forgot Password');
    }

    public function test_forgot_password_sends_reset_link()
    {
        Notification::fake();
        $user = $this->makeUser('operator');

        $this->post('/password/email', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_reveal_unknown_email()
    {
        $this->post('/password/email', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_flow_changes_password()
    {
        $user = $this->makeUser('operator');
        $token = Password::broker()->createToken($user);

        $this->get('/password/reset/' . $token . '?email=' . $user->email)
            ->assertOk()
            ->assertSee('Reset Password');

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('brandnewpass123', $user->fresh()->password));
        $this->assertFalse(Hash::check('password123', $user->fresh()->password));
    }

    public function test_superadmin_can_change_own_password()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/settings')
            ->assertOk();

        $this->actingAs($admin)
            ->put('/superadmin/settings/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword456',
                'new_password_confirmation' => 'newpassword456',
            ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword456', $admin->fresh()->password));
    }

    public function test_officer_can_change_own_password()
    {
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->get('/tfrb-officer/settings')
            ->assertOk();

        $this->actingAs($officer)
            ->put('/tfrb-officer/settings/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword456',
                'new_password_confirmation' => 'newpassword456',
            ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword456', $officer->fresh()->password));
    }

    public function test_change_password_requires_correct_current_password()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->put('/superadmin/settings/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword456',
                'new_password_confirmation' => 'newpassword456',
            ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password123', $admin->fresh()->password));
    }

    public function test_operator_can_view_own_profile()
    {
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        $this->actingAs($operator->user)
            ->get('/operator/profile')
            ->assertOk()
            ->assertSee($operator->user->name)
            ->assertSee($operator->plate_number)
            ->assertSee($operator->toda->name);
    }

    public function test_superadmin_can_export_operators_csv()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $this->actingAs($admin)
            ->get('/superadmin/operators/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="operators.csv"')
            ->assertSee($operator->user->name, false);
    }

    public function test_officer_can_export_operator_lists()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        foreach ([
            '/tfrb-officer/operators/export',
            '/tfrb-officer/reports/export',
            '/tfrb-officer/ratings/export',
            '/tfrb-officer/complaints/export',
            '/tfrb-officer/activity-logs/export',
        ] as $url) {
            $this->actingAs($officer)->get($url)->assertOk();
        }
    }

    public function test_superadmin_can_export_all_lists()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);
        ActivityLog::create(['user_id' => $admin->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        foreach ([
            '/superadmin/operators/export',
            '/superadmin/reports/export',
            '/superadmin/ratings/export',
            '/superadmin/complaints/export',
            '/superadmin/activity-logs/export',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_archive_operator_keeps_rating_history()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        $this->actingAs($admin)
            ->patch('/superadmin/operators/' . $operator->id . '/archive')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertNotNull($operator->fresh()->archived_at);
        $this->assertSame(1, $operator->fresh()->ratings()->count());
    }

    public function test_archived_operators_hidden_from_default_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->actingAs($admin)
            ->get('/superadmin/operators')
            ->assertOk()
            ->assertDontSee($operator->user->name);

        $this->actingAs($admin)
            ->get('/superadmin/operators?status=archived')
            ->assertOk()
            ->assertSee($operator->user->name);
    }

    public function test_restore_operator_returns_to_active_list()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->actingAs($admin)
            ->patch('/superadmin/operators/' . $operator->id . '/restore')
            ->assertRedirect(route('superadmin.operators', ['status' => 'archived']));

        $this->assertNull($operator->fresh()->archived_at);
    }

    public function test_officer_can_archive_and_restore_operator()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();

        $this->actingAs($officer)
            ->patch('/tfrb-officer/operators/' . $operator->id . '/archive')
            ->assertRedirect(route('tfrb-officer.operators'));

        $this->assertNotNull($operator->fresh()->archived_at);

        $this->actingAs($officer)
            ->patch('/tfrb-officer/operators/' . $operator->id . '/restore')
            ->assertRedirect(route('tfrb-officer.operators', ['status' => 'archived']));

        $this->assertNull($operator->fresh()->archived_at);
    }

    public function test_superadmin_can_deactivate_and_reactivate_operator_account()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $userId = $operator->user->id;

        $this->actingAs($admin)
            ->patch('/superadmin/operators/' . $operator->id . '/toggle-active')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertFalse((bool) User::find($userId)->is_active);
        $this->assertNotNull(ActivityLog::where('action', 'deactivate_operator')->latest()->first());

        $this->actingAs($admin)
            ->patch('/superadmin/operators/' . $operator->id . '/toggle-active')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertTrue((bool) User::find($userId)->is_active);
        $this->assertNotNull(ActivityLog::where('action', 'activate_operator')->latest()->first());
    }

    public function test_officer_can_toggle_operator_account()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();

        $this->actingAs($officer)
            ->patch('/tfrb-officer/operators/' . $operator->id . '/toggle-active')
            ->assertRedirect(route('tfrb-officer.operators'));

        $this->assertFalse((bool) $operator->fresh()->user->is_active);
    }

    public function test_deactivated_operator_cannot_login()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $operator->user->forceFill(['password' => Hash::make('password123')])->save();

        $this->actingAs($admin)
            ->patch('/superadmin/operators/' . $operator->id . '/toggle-active');

        $this->post('/login', [
            'email' => $operator->user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_account_filter_shows_only_matching_operators()
    {
        $admin = $this->makeUser('superadmin');
        $active = $this->makeOperator();
        $active->user->forceFill(['name' => 'Active Rider One'])->save();
        $inactive = $this->makeOperator();
        $inactive->user->forceFill(['name' => 'Inactive Rider Two'])->save();
        $inactive->user->forceFill(['is_active' => false])->save();

        $response = $this->actingAs($admin)->get('/superadmin/operators?account=active');
        $response->assertOk();
        $table = substr($response->getContent(), strpos($response->getContent(), '<tbody>'));
        $this->assertStringContainsString('Active Rider One', $table);
        $this->assertStringNotContainsString('Inactive Rider Two', $table);

        $response = $this->actingAs($admin)->get('/superadmin/operators?account=inactive');
        $response->assertOk();
        $table = substr($response->getContent(), strpos($response->getContent(), '<tbody>'));
        $this->assertStringContainsString('Inactive Rider Two', $table);
        $this->assertStringNotContainsString('Active Rider One', $table);
    }

    public function test_archived_operator_rate_form_is_404()
    {
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->get('/rate/' . $operator->qr_code)->assertNotFound();
    }

    public function test_archived_operator_cannot_receive_rating_submissions()
    {
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 5,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
        ])->assertNotFound();

        $this->assertSame(0, Rating::count());
    }

    public function test_archived_operator_cannot_login()
    {
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->post('/login', [
            'email' => $operator->user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_archived_operator_logged_in_is_blocked_from_dashboard()
    {
        $operator = $this->makeOperator();
        $operator->update(['archived_at' => now()]);

        $this->actingAs($operator->user)
            ->get('/operator/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_can_assign_toda_president_and_demote_previous()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $first = $this->makeOperator();
        $first->update(['toda_id' => $toda->id]);
        $second = $this->makeOperator();
        $second->update(['toda_id' => $toda->id]);

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $first->id . '/assign-president')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertSame('operator_president', $first->user->fresh()->role);
        $this->assertNotNull(ActivityLog::where('action', 'assign_toda_president')->latest()->first());

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $second->id . '/assign-president')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertSame('operator_president', $second->user->fresh()->role);
        $this->assertSame('operator', $first->user->fresh()->role);
        $this->assertSame(1, $toda->president()->count());
    }

    public function test_officer_can_assign_toda_president()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $operator = $this->makeOperator();
        $operator->update(['toda_id' => $toda->id]);

        $this->actingAs($officer)
            ->post('/tfrb-officer/operators/' . $operator->id . '/assign-president')
            ->assertRedirect(route('tfrb-officer.operators'));

        $this->assertSame('operator_president', $operator->user->fresh()->role);
        $this->assertSame(1, $toda->president()->count());
    }

    public function test_officer_can_reset_operator_password_and_email_is_sent()
    {
        Mail::fake();
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $operator->user->forceFill(['password' => Hash::make('oldpass123')])->save();

        $this->actingAs($officer)
            ->post('/tfrb-officer/operators/' . $operator->id . '/reset-password')
            ->assertRedirect(route('tfrb-officer.operators'))
            ->assertSessionHas('success');

        $this->assertFalse(Hash::check('oldpass123', $operator->user->fresh()->password));
        $this->assertNotNull(ActivityLog::where('action', 'reset_operator_password')->latest()->first());
        Mail::assertSent(OperatorCredentials::class);
    }

    public function test_superadmin_can_reset_operator_password()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $oldHash = $operator->user->password;

        $this->actingAs($admin)
            ->post('/superadmin/operators/' . $operator->id . '/reset-password')
            ->assertRedirect(route('superadmin.operators'));

        $this->assertNotSame($oldHash, $operator->user->fresh()->password);
    }

    public function test_ratings_page_filters_by_operator_and_date()
    {
        $admin = $this->makeUser('superadmin');
        $a = $this->makeOperator();
        $a->user->forceFill(['name' => 'Rating Filter Alpha'])->save();
        $b = $this->makeOperator();
        $b->user->forceFill(['name' => 'Rating Filter Beta'])->save();
        $this->makeValidRating($a);
        $this->makeValidRating($b);

        $response = $this->actingAs($admin)->get('/superadmin/ratings?operator_id=' . $a->id);
        $response->assertOk();
        $this->assertEquals(1, substr_count($response->getContent(), 'data-rating-card'));

        $response = $this->actingAs($admin)->get('/superadmin/ratings?date_from=' . now()->toDateString() . '&date_to=' . now()->toDateString());
        $response->assertOk();
        $this->assertEquals(2, substr_count($response->getContent(), 'data-rating-card'));
    }

    public function test_reports_page_filters_by_toda_and_min_rating()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        $this->actingAs($admin)
            ->get('/superadmin/reports?toda_id=' . $operator->toda_id . '&min_rating=3')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/superadmin/reports?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk();
    }

    public function test_reports_pdf_export_includes_filter_context()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $this->makeValidComplaint($operator);

        $response = $this->actingAs($admin)->get('/superadmin/reports/export?format=pdf&toda_id=' . $operator->toda_id . '&date_from=2026-01-01&date_to=2026-12-31');
        $response->assertOk();
        $this->assertStringContainsString('TODA:', $response->getContent());
        $this->assertStringContainsString('Period:', $response->getContent());
    }

    public function test_operator_can_upload_proof_attachment_in_response()
    {
        Storage::fake('public');
        $operator = $this->makeOperator();
        $rating = $this->makeValidComplaint($operator);

        $this->actingAs($operator->user)
            ->post('/operator/ratings/' . $rating->id . '/respond', [
                'message' => 'Here is my proof.',
                'files' => [UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->assertSame(1, OperatorProof::count());
        Storage::disk('public')->assertExists(OperatorProof::first()->file_path);
    }

    public function test_operator_cannot_respond_to_someone_elses_rating_with_proofs()
    {
        Storage::fake('public');
        $owner = $this->makeOperator();
        $intruder = $this->makeOperator();
        $rating = $this->makeValidComplaint($owner);

        $this->actingAs($intruder->user)
            ->post('/operator/ratings/' . $rating->id . '/respond', [
                'message' => 'nope',
                'files' => [UploadedFile::fake()->create('bad.pdf', 100, 'application/pdf')],
            ]);

        $this->assertSame(0, OperatorProof::count());
    }

    public function test_database_migrations_can_build_from_scratch()
    {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('operator_response_proofs'));
    }
}
