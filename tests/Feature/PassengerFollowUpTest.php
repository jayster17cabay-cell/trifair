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
                'message' => 'What is the status of this complaint?',
            ])
            ->assertRedirect(route('passenger.dashboard'))
            ->assertSessionHas('status')
            ->assertSessionHas('open_follow_up', $rating->id);

        $this->assertDatabaseHas('passenger_follow_ups', [
            'rating_id' => $rating->id,
            'user_id' => $passenger->id,
            'message' => 'What is the status of this complaint?',
        ]);

        // The thread reads back on their own complaints page, and the card plus
        // chat arrive already expanded so their own message is on screen.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Your follow-ups (1)')
            ->assertSee('What is the status of this complaint?')
            ->assertDontSee('data-chat-body class="hidden', false)
            ->assertDontSee('data-complaint-details class="hidden', false);
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
                'message' => 'Hope this gets resolved.',
            ])
            ->assertRedirect('/passenger/dashboard')
            ->assertSessionHasErrors('follow_up');

        $this->assertDatabaseCount('passenger_follow_ups', 0);

        // The form is withdrawn for a solved ticket — nothing left to chase.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertDontSee('Follow up this complaint', false)
            ->assertSee('you can no longer follow up', false);
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
                'message' => 'I have additional evidence for my NBI clearance.',
            ])->assertRedirect();

        $html = $this->actingAs($admin)
            ->get('/superadmin/complaints')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Passenger Follow-ups', $html);
        $this->assertStringContainsString('I have additional evidence for my NBI clearance.', $html);
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

    public function test_superadmin_can_reply_to_the_passenger_chat()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($passenger)
            ->post(route('passenger.complaints.followUp', $rating), [
                'follow_up_rating_id' => (string) $rating->id,
                'message' => 'Any update on my complaint?',
            ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('superadmin.complaints.reply', $rating), [
                'message' => 'We have summoned the driver for a hearing.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('passenger_follow_ups', [
            'rating_id' => $rating->id,
            'user_id' => $admin->id,
            'sender_role' => 'staff',
            'message' => 'We have summoned the driver for a hearing.',
        ]);

        // The passenger reads the staff reply — labelled by role only, never
        // the reviewer's personal name.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('We have summoned the driver for a hearing.')
            ->assertSee('Superadmin')
            ->assertDontSee($admin->name);

        // An in-app notification tells them to come back and read it.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $passenger->id,
            'rating_id' => $rating->id,
            'type' => 'complaint_status',
            'title' => 'Complaint Update',
        ]);
    }

    public function test_tfrb_officer_can_reply_to_the_passenger_chat()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->post(route('tfrb-officer.complaints.reply', $rating), [
                'message' => 'Please check your email for the hearing schedule.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('passenger_follow_ups', [
            'rating_id' => $rating->id,
            'user_id' => $officer->id,
            'sender_role' => 'staff',
            'message' => 'Please check your email for the hearing schedule.',
        ]);

        // The passenger sees the reply under the officer's role, not their name.
        $this->actingAs($passenger)
            ->get('/passenger/dashboard')
            ->assertOk()
            ->assertSee('Please check your email for the hearing schedule.')
            ->assertSee('TFRB Officer')
            ->assertDontSee($officer->name);
    }

    public function test_staff_chat_is_closed_once_the_complaint_is_solved()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $rating->update(['is_solved' => true, 'is_accepted' => true]);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->from('/superadmin/complaints')
            ->post(route('superadmin.complaints.reply', $rating), [
                'message' => 'Closing note after resolution.',
            ])
            ->assertRedirect('/superadmin/complaints')
            ->assertSessionHasErrors('chat');

        $this->assertDatabaseCount('passenger_follow_ups', 0);

        // The staff composer is withdrawn from the admin card, same as the passenger side.
        $html = $this->actingAs($admin)
            ->get('/superadmin/complaints')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('the chat is closed', $html);
        $this->assertStringNotContainsString('Send an update to the passenger', $html);
    }

    public function test_staff_reply_requires_a_message()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->from('/superadmin/complaints')
            ->post(route('superadmin.complaints.reply', $rating), [
                'message' => '',
            ])
            ->assertRedirect('/superadmin/complaints')
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }

    public function test_passenger_cannot_use_the_staff_reply_route()
    {
        $passenger = $this->makeUser('passenger');
        $rating = $this->makeComplaint($this->makeOperator(), $passenger);

        $this->actingAs($passenger)
            ->post(route('superadmin.complaints.reply', $rating), [
                'message' => 'Writing as if I were staff.',
            ])->assertForbidden();

        $this->assertDatabaseCount('passenger_follow_ups', 0);
    }
}
