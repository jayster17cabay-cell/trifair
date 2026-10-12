<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RatingFlowTest extends TestCase
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

    private function makeActiveOperator(): Operator
    {
        $toda = Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);

        $user = new User();
        $user->forceFill([
            'name' => 'Test Operator',
            'email' => 'op_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => 'operator',
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $user->save();

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => 'qr-' . Str::random(16),
            'status' => 'active',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    public function test_valid_high_rating_is_flagged_valid_and_notifies_officers()
    {
        $this->makeOfficer('superadmin');
        $this->makeOfficer('tfrb_officer');
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 5,
            'start_location' => 'Quirino Highway, Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertTrue((bool) $rating->is_valid);
        $this->assertStringContainsString('Novaliches', $rating->start_location);
        $this->assertEquals(2, Notification::count());
    }

    public function test_incomplete_complaint_submission_is_rejected()
    {
        $this->makeOfficer('superadmin');
        $operator = $this->makeActiveOperator();

        // A complaint missing its type/details/email/proof must not go through.
        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 2,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertSessionHasErrors(['complaint_type', 'passenger_email', 'proofs']);

        $this->assertEquals(0, Rating::count());
    }

    public function test_complete_complaint_submission_is_accepted_and_notifies_officers()
    {
        $this->makeOfficer('superadmin');
        $this->makeOfficer('tfrb_officer');
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 2,
            'start_location' => 'A',
            'end_location' => 'B',
            'complaint_type' => 'Rude Driver',
            'passenger_email' => 'passenger@example.com',
            'proofs' => [UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertTrue((bool) $rating->is_valid);
        $this->assertEquals('Rude Driver', $rating->complaint_type);
        $this->assertSame(1, $rating->proofs()->count());
        $this->assertEquals(2, Notification::count());
    }

    public function test_complaint_with_empty_details_field_is_accepted()
    {
        $this->makeOfficer('superadmin');
        $operator = $this->makeActiveOperator();

        // The complaint form always posts the (hidden) details textarea, so it
        // arrives as an empty string. It must not be rejected as "not a string"
        // when the type is not "Others".
        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 1,
            'start_location' => 'A',
            'end_location' => 'B',
            'complaint_type' => 'Rude Driver',
            'complaint_details' => '',
            'passenger_email' => 'passenger@example.com',
            'proofs' => [UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
        ])->assertRedirect(route('rate.submitted', $operator->qr_code))
            ->assertSessionHasNoErrors();

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertSame('Rude Driver', $rating->complaint_type);
        $this->assertNull($rating->complaint_details);
    }

    public function test_others_complaint_requires_details()
    {
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 1,
            'start_location' => 'A',
            'end_location' => 'B',
            'complaint_type' => 'Others',
            'complaint_details' => '',
            'passenger_email' => 'passenger@example.com',
            'proofs' => [UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
        ])->assertSessionHasErrors('complaint_details');

        $this->assertEquals(0, Rating::count());
    }

    public function test_rating_without_locations_is_invalid()
    {
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 5,
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertFalse((bool) $rating->is_valid);
    }

    public function test_empty_complaint_fields_migration_cleans_legacy_data()
    {
        $operator = $this->makeActiveOperator();

        // Simulate the legacy bug: positive ratings stored with an empty-string
        // complaint type/details, which misclassified them as "complaints".
        DB::table('ratings')->insert([
            'operator_id' => $operator->id,
            'rating' => 5,
            'complaint_type' => '',
            'complaint_details' => '',
            'is_valid' => false,
            'is_auto' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $goodId = (int) DB::getPdo()->lastInsertId();

        DB::table('migrations')
            ->where('migration', '2026_10_08_000001_normalize_empty_complaint_fields')
            ->delete();

        Artisan::call('migrate', ['--force' => true]);

        $good = Rating::find($goodId);

        $this->assertNull($good->complaint_type);
        $this->assertNull($good->complaint_details);
        // A rating without a From/To trip is still invalid — the trip is required.
        $this->assertFalse((bool) $good->is_valid);
    }

    public function test_out_of_range_rating_is_rejected()
    {
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 99,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertSessionHasErrors('rating');

        $this->assertEquals(0, Rating::count());
    }

    public function test_duplicate_submission_same_day_is_rejected()
    {
        $operator = $this->makeActiveOperator();

        $payload = [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ];

        // Same device = same tf_pid cookie on both submissions.
        $cookie = 'device-' . Str::random(20);

        $this->withCookie('tf_pid', $cookie)
            ->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code));

        $this->withCookie('tf_pid', $cookie)
            ->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code))
            ->assertSessionHas('alreadyRated');

        $this->assertEquals(1, Rating::count());
    }

    public function test_passengers_sharing_one_public_ip_can_each_rate_once()
    {
        $operator = $this->makeActiveOperator();

        $payload = [
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
        ];

        // Two different phones behind the same public IP (mobile carrier NAT
        // or shared terminal Wi-Fi). Neither should be flagged as a duplicate.
        $this->withCookie('tf_pid', 'phone-one')
            ->withServerVariables(['REMOTE_ADDR' => '203.177.0.10'])
            ->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code));

        $this->withCookie('tf_pid', 'phone-two')
            ->withServerVariables(['REMOTE_ADDR' => '203.177.0.10'])
            ->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code))
            ->assertSessionMissing('alreadyRated');

        $this->assertEquals(2, Rating::count());
    }

    public function test_rate_form_is_blocked_for_inactive_operator()
    {
        $operator = $this->makeActiveOperator();
        $operator->update(['status' => 'inactive']);

        $this->get('/rate/' . $operator->qr_code)->assertNotFound();
    }

    public function test_thank_you_close_sends_guests_to_landing_page()
    {
        $operator = $this->makeActiveOperator();

        $this->get(route('rate.submitted', $operator->qr_code))
            ->assertOk()
            ->assertSee("window.location.replace('/')", false);
    }

    public function test_thank_you_close_sends_signed_in_passenger_to_their_dashboard()
    {
        $operator = $this->makeActiveOperator();

        $passenger = new User();
        $passenger->forceFill([
            'name' => 'Test Passenger',
            'email' => 'passenger_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => 'passenger',
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $passenger->save();

        $this->actingAs($passenger)
            ->get(route('rate.submitted', $operator->qr_code))
            ->assertOk()
            ->assertSee(route('passenger.dashboard'), false);
    }

    public function test_thank_you_view_all_button_only_for_signed_in_passenger()
    {
        $operator = $this->makeActiveOperator();

        $this->get(route('rate.submitted', $operator->qr_code))
            ->assertOk()
            ->assertDontSee('View All My Ratings');

        $passenger = new User();
        $passenger->forceFill([
            'name' => 'Test Passenger',
            'email' => 'passenger_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => 'passenger',
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        $passenger->save();

        $this->actingAs($passenger)
            ->get(route('rate.submitted', $operator->qr_code))
            ->assertOk()
            ->assertSee('View All My Ratings');
    }

    public function test_operator_account_cannot_submit_rating()
    {
        $operator = $this->makeActiveOperator();

        $this->actingAs($operator->user)
            ->post('/rate/' . $operator->qr_code, [
                'rating' => 5,
                'start_location' => 'A',
                'end_location' => 'B',
            ])->assertSessionHasErrors('email');

        $this->assertEquals(0, Rating::count());
    }

    public function test_operator_rate_form_shows_blocked_notice()
    {
        $operator = $this->makeActiveOperator();

        $this->actingAs($operator->user)
            ->get('/rate/' . $operator->qr_code)
            ->assertOk()
            ->assertSee('Operator/Staff account');
    }
}
