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
 * The president's "Ratings & Complaints" page — their personal feedback as an
 * operator, restated inside the president console: headline stats, complaint
 * review states, the response form for low ratings, all scoped to the TODA
 * they govern.
 */
class PresidentRatingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'operator', ?string $name = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $name ?? 'Test ' . ucfirst(str_replace('_', ' ', $role)),
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
            'name' => 'TODA ' . Str::upper(Str::random(5)),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(User $user, Toda $toda): Operator
    {
        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    /** An operator account that also presides over the given TODA. */
    private function makePresident(User $user, Toda $toda): Operator
    {
        $user->forceFill([
            'role' => 'operator_president',
            'toda_id' => $user->toda_id ?: $toda->id,
        ])->save();

        $operator = $this->makeOperator($user, $toda);
        $operator->setTodaPresidency($toda->id, true);

        return $operator;
    }

    private function makeRating(
        Operator $operator,
        int $stars,
        string $reason = 'Trip feedback',
        ?string $complaintType = null,
        ?string $complaintDetails = null,
        ?int $todaId = null
    ): Rating {
        return Rating::create([
            'operator_id' => $operator->id,
            'toda_id' => $todaId ?? $operator->toda_id,
            'rating' => $stars,
            'reason' => $reason,
            'start_location' => 'Pickup',
            'end_location' => 'Dropoff',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
            'complaint_type' => $complaintType,
            'complaint_details' => $complaintDetails,
        ]);
    }

    public function test_president_opens_their_own_ratings_page(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $operator = $this->makePresident($pres, $toda);
        $this->makeRating($operator, 5, 'Smooth trip, very professional.');

        $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->assertSee('My Ratings & Complaints')
            ->assertSee($toda->name)
            ->assertSee('Smooth trip, very professional.')
            ->assertSee('Avg Rating')
            ->assertSee('Needs Response')
            ->assertSee('All Ratings');
    }

    public function test_complaints_filter_lists_only_complaints_with_review_status(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $operator = $this->makePresident($pres, $toda);

        $this->makeRating($operator, 2, 'Driver cut me off', 'Reckless Driving', 'Tailgated me the whole trip');
        $this->makeRating($operator, 5, 'Perfect ride as always');

        // The default view carries every valid row.
        $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->assertSee('Perfect ride as always')
            ->assertSee('Tailgated me the whole trip');

        // Complaints only: the plain 5-star row drops out and the review
        // state of the complaint becomes visible to the person it names.
        $this->actingAs($pres)
            ->get('/president/ratings?filter=complaints')
            ->assertOk()
            ->assertSee('Tailgated me the whole trip')
            ->assertSee('Under review')
            ->assertDontSee('Perfect ride as always');
    }

    public function test_needs_response_filter_shows_only_unanswered_low_ratings(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $operator = $this->makePresident($pres, $toda);

        $low = $this->makeRating($operator, 1, 'Refused to stop for me');
        $this->makeRating($operator, 4, 'Comfortable trip');

        $this->actingAs($pres)
            ->get('/president/ratings?filter=needs_response')
            ->assertOk()
            ->assertSee('Refused to stop for me')
            ->assertSee('This rating needs your response')
            ->assertDontSee('Comfortable trip');

        // Responding from the page clears it from the queue and shows back
        // as the recorded response on the full list.
        $this->actingAs($pres)
            ->from('/president/ratings?filter=needs_response')
            ->post(route('president.ratings.respond', $low), [
                'message' => 'I stopped at the next corner.',
            ])
            ->assertRedirect('/president/ratings?filter=needs_response');

        $this->assertDatabaseHas('operator_responses', [
            'rating_id' => $low->id,
            'message' => 'I stopped at the next corner.',
        ]);

        $this->actingAs($pres)
            ->get('/president/ratings?filter=needs_response')
            ->assertOk()
            ->assertDontSee('This rating needs your response');

        $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->assertSee('Your Response')
            ->assertSee('I stopped at the next corner.');
    }

    public function test_president_cannot_respond_to_another_operators_rating(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $this->makePresident($pres, $toda);

        $otherDriver = $this->makeUser('operator', 'Other Driver');
        $otherOperator = $this->makeOperator($otherDriver, $toda);
        $foreign = $this->makeRating($otherOperator, 1, 'Not my driver at all');

        $this->actingAs($pres)
            ->from('/president/ratings')
            ->post(route('president.ratings.respond', $foreign), [
                'message' => 'Claiming someone else\'s rating.',
            ])
            ->assertRedirect('/president/ratings')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('operator_responses', 0);
    }

    public function test_ratings_page_is_scoped_to_the_governed_toda(): void
    {
        $todaA = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $operator = $this->makePresident($pres, $todaA);

        $todaB = $this->makeToda();
        $this->makeRating($operator, 5, 'Rated under TODA B', null, null, (int) $todaB->id);
        $this->makeRating($operator, 5, 'Rated under TODA A');

        $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->assertSee('Rated under TODA A')
            ->assertDontSee('Rated under TODA B');
    }

    public function test_non_presidents_cannot_open_the_ratings_page(): void
    {
        $toda = $this->makeToda();
        $operatorUser = $this->makeUser('operator');
        $this->makeOperator($operatorUser, $toda);

        $this->actingAs($operatorUser)
            ->get('/president/ratings')
            ->assertForbidden();

        $superadmin = $this->makeUser('superadmin');

        $this->actingAs($superadmin)
            ->get('/president/ratings')
            ->assertForbidden();
    }

    public function test_sidebar_offers_the_ratings_and_complaints_link(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $this->makePresident($pres, $toda);

        $html = $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->getContent();

        $asideStart = strpos($html, '<aside');
        $aside = substr($html, $asideStart, strpos($html, '</aside>') - $asideStart);

        $this->assertStringContainsString(route('president.ratings'), $aside);
        $this->assertStringContainsString('Ratings &amp; Complaints', $aside);
    }

    public function test_dashboard_card_links_to_the_full_ratings_page(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $operator = $this->makePresident($pres, $toda);
        $this->makeRating($operator, 2, 'Late arrival at the terminal', 'Late Arrival', null);

        $html = $this->actingAs($pres)
            ->get('/president/dashboard')
            ->assertOk()
            ->getContent();

        // "View all" on the summary card opens the full page.
        $this->assertStringContainsString(route('president.ratings'), $html);
        $this->assertStringContainsString('View all', $html);

        // A pending complaint shows up in the recent list with its review
        // state — the president is never left guessing about new complaints.
        $this->assertStringContainsString('Under review', $html);
        $this->assertStringContainsString('Late arrival at the terminal', $html);
    }

    public function test_ratings_page_renders_an_empty_state(): void
    {
        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $this->makePresident($pres, $toda);

        $this->actingAs($pres)
            ->get('/president/ratings')
            ->assertOk()
            ->assertSee('No ratings yet.')
            ->assertSee('Once passengers scan your QR code');
    }
}
