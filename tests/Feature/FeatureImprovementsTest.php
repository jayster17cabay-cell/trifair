<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
        ]);

        RatingProof::create([
            'rating_id' => $rating->id,
            'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $rating;
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
}
