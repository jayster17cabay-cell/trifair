<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_low_rating_without_proof_is_invalid_and_sends_no_notification()
    {
        $this->makeOfficer('superadmin');
        $operator = $this->makeActiveOperator();

        $this->post('/rate/' . $operator->qr_code, [
            'rating' => 2,
            'start_location' => 'A',
            'end_location' => 'B',
        ])->assertRedirect(route('rate.submitted', $operator->qr_code));

        $rating = Rating::first();
        $this->assertNotNull($rating);
        $this->assertFalse((bool) $rating->is_valid);
        $this->assertEquals(0, Notification::count());
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

        $this->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code));

        $this->post('/rate/' . $operator->qr_code, $payload)
            ->assertRedirect(route('rate.submitted', $operator->qr_code))
            ->assertSessionHas('alreadyRated');

        $this->assertEquals(1, Rating::count());
    }

    public function test_rate_form_is_blocked_for_inactive_operator()
    {
        $operator = $this->makeActiveOperator();
        $operator->update(['status' => 'inactive']);

        $this->get('/rate/' . $operator->qr_code)->assertNotFound();
    }
}
