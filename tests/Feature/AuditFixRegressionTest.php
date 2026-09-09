<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeOfficer(string $role = 'superadmin'): User
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

    private function makeOperatorUser(bool $verified = true, string $status = 'pending'): array
    {
        $toda = Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Solano',
            'is_active' => true,
        ]);

        $user = new User();
        $user->forceFill([
            'name' => 'Pending Operator',
            'email' => 'op_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => $verified ? now() : null,
            'role' => 'operator',
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $user->save();

        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => 'qr-' . Str::random(16),
            'status' => $status,
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);

        return [$user, $operator];
    }

    public function test_raw_tf_pid_cookie_is_respected_for_rating_identity_continuity()
    {
        [, $operator] = $this->makeOperatorUser(true, 'active');

        // Simulate the client-side (SOS partial) raw cookie already being set.
        $cookie = 'raw-device-' . Str::random(20);

        $this->withUnencryptedCookies(['tf_pid' => $cookie])
            ->post('/rate/' . $operator->qr_code, [
                'rating' => 5,
                'start_location' => 'Start St',
                'end_location' => 'End St',
            ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::firstOrFail();
        $this->assertSame($cookie, $rating->client_id);

        // Same device cookie from a different IP must still be deduped.
        $this->withUnencryptedCookies(['tf_pid' => $cookie])
            ->withServerVariables(['REMOTE_ADDR' => '192.168.99.99'])
            ->get('/rate/' . $operator->qr_code)
            ->assertRedirect(route('rate.submitted', $operator->qr_code))
            ->assertSessionHas('alreadyRated');
    }

    public function test_superadmin_ratings_review_route_marks_rating_reviewed()
    {
        $admin = $this->makeOfficer('superadmin');
        [, $operator] = $this->makeOperatorUser(true, 'active');

        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 5,
            'start_location' => 'Start St',
            'end_location' => 'End St',
            'passenger_ip' => '127.0.0.1',
            'is_valid' => true,
            'is_auto' => false,
        ]);

        $this->actingAs($admin)
            ->patch('/superadmin/ratings/' . $rating->id . '/review')
            ->assertSessionHas('success');

        $this->assertTrue((bool) $rating->fresh()->is_reviewed);
    }

    public function test_superadmin_ratings_page_compiles_with_dedicated_review_route()
    {
        $admin = $this->makeOfficer('superadmin');
        [, $operator] = $this->makeOperatorUser(true, 'active');

        Rating::create([
            'operator_id' => $operator->id,
            'rating' => 4,
            'start_location' => 'Start St',
            'end_location' => 'End St',
            'passenger_ip' => '127.0.0.1',
            'is_valid' => true,
            'is_auto' => false,
        ]);

        $this->actingAs($admin)
            ->get('/superadmin/ratings')
            ->assertOk()
            ->assertStatus(200);
    }

    public function test_operator_create_form_marks_plate_and_body_as_required()
    {
        $admin = $this->makeOfficer('superadmin');

        $response = $this->actingAs($admin)
            ->get('/superadmin/operators/create')
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('Plate Number <span class="text-red-600">*</span>', $html);
        $this->assertStringContainsString('Body Number <span class="text-red-600">*</span>', $html);
        $this->assertMatchesRegularExpression('/name="plate_number"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="body_number"[^>]*required/', $html);
    }

    public function test_pending_operator_page_shows_email_verification_resend_for_unverified()
    {
        [$user, $operator] = $this->makeOperatorUser(false, 'pending');

        $response = $this->actingAs($user)
            ->get('/operator/pending')
            ->assertOk()
            ->assertSee('Account Pending Approval');

        $response->assertSee('Resend Verification Link');
    }

    public function test_verification_verify_redirects_by_role()
    {
        // Pending operator -> operator.pending
        [$operatorUser] = $this->makeOperatorUser(false, 'pending');
        $url = \Illuminate\Support\Facades\URL::signedRoute('verification.verify', [
            'id' => $operatorUser->id,
            'hash' => sha1($operatorUser->getEmailForVerification()),
        ]);
        $this->actingAs($operatorUser)->get($url)->assertRedirect(route('operator.pending'));
        $this->assertNotNull($operatorUser->fresh()->email_verified_at);

        // Officer -> officer dashboard (no 403 on operator.pending)
        $officer = $this->makeOfficer('tfrb_officer');
        $officer->forceFill(['email_verified_at' => null])->save();
        $url = \Illuminate\Support\Facades\URL::signedRoute('verification.verify', [
            'id' => $officer->id,
            'hash' => sha1($officer->getEmailForVerification()),
        ]);
        $this->actingAs($officer)->get($url)->assertRedirect(route('tfrb-officer.dashboard'));
        $this->assertNotNull($officer->fresh()->email_verified_at);
    }

    public function test_pending_operator_page_uses_resend_route_action()
    {
        [$user] = $this->makeOperatorUser(false, 'pending');

        $this->actingAs($user)
            ->from('/operator/pending')
            ->post('/email/resend')
            ->assertRedirect('/operator/pending')
            ->assertSessionHas('message', 'Verification link sent!');

        $this->actingAs($user)
            ->post('/email/resend')
            ->assertSessionHas('message', 'Verification link sent!');
    }
}