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

/**
 * The passenger "follow up" thread: an extra message added to their own open
 * complaint, then shown back to them and to the TFRB/superadmin reviewers.
 */
class PassengerFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'passenger'): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test ' . ucfirst(str_replace('_', ' ', $role)),
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

    private function makeOperator(): Operator
    {
        $toda = Toda::create([
            'name' => 'TODA ' . Str::upper(Str::random(5)),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);

        return Operator::create([
            'user_id' => $this->makeUser('operator')->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makeComplaint(Operator $operator, User $passenger): Rating
    {
        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 1,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
        $rating->update([
            'complaint_type' => 'Rude Driver',
            'complaint_details' => 'Rude to passenger',
            'passenger_user_id' => $passenger->id,
        ]);

        return $rating->fresh();
    }

    public function test_passenger_can_follow_up_on_their_own_open_complaint()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);

        $this->actingAs($passenger)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'Kumusta po ang status nito?',
            ])
            ->assertRedirect(route('passenger.dashboard'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('passenger_follow_ups', [
            'rating_id' => $rating->id,
            'user_id' => $passenger->id,
            'message' => 'Kumusta po ang status nito?',
        ]);

        // The thread reads back on their own complaints page.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Your follow-ups (1)')
            ->assertSee('Kumusta po ang status nito?');
    }

    public function test_passenger_cannot_follow_up_on_another_passengers_complaint()
    {
        $owner = $this->makeUser('passenger');
        $other = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $owner);

        $this->actingAs($other)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'I am not the person who filed this.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }

    public function test_follow_up_is_refused_once_the_complaint_is_solved()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $rating->update(['is_solved' => true, 'is_accepted' => true]);

        $this->actingAs($passenger)
            ->from('/passenger/dashboard')
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'Sana ma-solve na po.',
            ])
            ->assertRedirect('/passenger/dashboard')
            ->assertSessionHasErrors('follow_up');

        $this->assertDatabaseCount('passenger_follow_ups', 0);

        // The form is withdrawn for a solved ticket — nothing left to chase.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertDontSee('Follow up this complaint', false)
            ->assertSee('hindi na po pwedeng mag-follow up', false);
    }

    public function test_follow_up_requires_a_message()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);

        $this->actingAs($passenger)
            ->from('/passenger/dashboard')
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => '',
            ])
            ->assertRedirect('/passenger/dashboard')
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }

    public function test_follow_up_is_refused_for_a_non_complaint_rating()
    {
        $passenger = $this->makeUser('passenger');
        $operator = $this->makeOperator();

        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 5,
            'start_location' => 'A',
            'end_location' => 'B',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
        $rating->update(['passenger_user_id' => $passenger->id]);

        $this->actingAs($passenger)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'This is a plain 5-star rating.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }

    public function test_staff_accounts_cannot_use_the_passenger_follow_up_route()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $operator = $rating->operator;

        $this->actingAs($operator->user)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'An operator writing into a passenger thread.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }

    public function test_admin_complaint_card_shows_the_follow_ups()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($passenger)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'May dagdag po akong ebidensya sa NBI clearance ko.',
            ])->assertRedirect();

        $html = $this->actingAs($admin)
            ->get('/superadmin/complaints')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Passenger Follow-ups', $html);
        $this->assertStringContainsString('May dagdag po akong ebidensya sa NBI clearance ko.', $html);
        $this->assertStringContainsString('1 follow-up', $html);
    }

    public function test_another_passenger_sees_no_follow_up_they_did_not_write()
    {
        $owner = $this->makeUser('passenger');
        $other = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $owner);

        $this->actingAs($owner)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'Only the filing passenger should read this.',
            ])->assertRedirect();

        // A different passenger has no complaints of their own at all.
        $this->actingAs($other)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertDontSee('Only the filing passenger should read this.', false)
            ->assertSee('No complaints yet');
    }
}
