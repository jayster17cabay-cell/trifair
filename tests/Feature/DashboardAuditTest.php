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

/**
 * End-to-end dashboard audit: every list page, filter, search, AJAX surface and
 * primary button action is exercised so a broken route, query or view is caught
 * before the system goes live.
 */
class DashboardAuditTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?Toda $toda = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => ucfirst(str_replace('_', ' ', $role)) . ' ' . Str::random(4),
            'email' => $role . '_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
            'toda_id' => $toda?->id,
        ]);
        $user->save();

        return $user;
    }

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'TODA ' . Str::random(6),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(string $status = 'active', ?Toda $toda = null, bool $archived = false): Operator
    {
        $operator = Operator::create([
            'user_id' => $this->makeUser('operator')->id,
            'toda_id' => ($toda ?? $this->makeToda())->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);

        if ($archived) {
            $operator->update(['archived_at' => now()]);
        }

        return $operator;
    }

    private function makeRating(Operator $operator, int $stars = 5, bool $valid = true, bool $complaint = false): Rating
    {
        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => $stars,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => $valid,
            'is_reviewed' => false,
            'is_auto' => false,
            'complaint_type' => $complaint ? 'Rude Driver' : null,
            'complaint_details' => $complaint ? 'Rude to passenger' : null,
        ]);

        if ($complaint) {
            RatingProof::create([
                'rating_id' => $rating->id,
                'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
                'file_type' => 'image/jpeg',
                'original_name' => 'proof.jpg',
            ]);
        }

        return $rating;
    }

    private function seedOperators(): array
    {
        $toda = $this->makeToda();
        $active = $this->makeOperator('active', $toda);
        $pending = $this->makeOperator('pending', $toda);
        $rejected = $this->makeOperator('rejected', $toda);
        $archived = $this->makeOperator('active', $toda, archived: true);

        $this->makeRating($active, 5);
        $this->makeRating($active, 2, true, true);
        $this->makeRating($active, 1, false);

        return compact('toda', 'active', 'pending', 'rejected', 'archived');
    }

    public function test_superadmin_pages_searches_and_ajax_render()
    {
        $admin = $this->makeUser('superadmin');
        $data = $this->seedOperators();
        $president = $this->makeUser('operator_president', $data['toda']);
        $this->makeUser('tfrb_officer');
        ActivityLog::create(['user_id' => $admin->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        $toda = $data['toda'];
        $active = $data['active'];

        $urls = [
            '/superadmin/dashboard',
            '/superadmin/operators',
            '/superadmin/operators?search=' . urlencode($active->user->name),
            '/superadmin/operators?search=' . urlencode($active->body_number),
            '/superadmin/operators?status=pending',
            '/superadmin/operators?status=archived',
            '/superadmin/operators?account=inactive',
            '/superadmin/operators?status=bogus&account=bogus&search=%25_%25',
            '/superadmin/operators?page=999',
            '/superadmin/operators?search=' . urlencode('<script>alert(1)</script>'),
            '/superadmin/operators/create',
            '/superadmin/operators/' . $active->id . '/edit',
            '/superadmin/operators/' . $active->id . '/qrcode',
            '/superadmin/officers',
            '/superadmin/officers?search=tfrb',
            '/superadmin/officers/create',
            '/superadmin/presidents',
            '/superadmin/presidents?search=' . urlencode($president->name),
            '/superadmin/ratings',
            '/superadmin/ratings?date_from=2020-01-01&date_to=2030-12-31&operator_id=' . $active->id,
            '/superadmin/ratings?date_from=bogus&date_to=bogus&operator_id=999999',
            '/superadmin/complaints',
            '/superadmin/complaints?filter=all',
            '/superadmin/complaints?filter=reviewed',
            '/superadmin/complaints?filter=bogus',
            '/superadmin/reports',
            '/superadmin/reports?toda_id=' . $toda->id . '&min_rating=1&date_from=2020-01-01&date_to=2030-12-31',
            '/superadmin/reports?min_rating=bogus&toda_id=999999&date_from=bogus',
            '/superadmin/invalid-ratings',
            '/superadmin/activity-logs',
            '/superadmin/activity-logs?category=auth',
            '/superadmin/activity-logs?category=bogus',
            '/superadmin/todas',
            '/superadmin/todas?search=' . urlencode($toda->name),
            '/superadmin/todas/create',
            '/superadmin/todas/' . $toda->id . '/edit',
            '/superadmin/settings',
            '/notifications',
            '/notifications?type=unread',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk('Failed on: ' . $url);
        }

        $this->actingAs($admin)->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk()->assertJsonStructure(['html', 'count']);
        $this->actingAs($admin)->getJson('/superadmin/reports/operators/' . $active->id . '/trips')
            ->assertOk()->assertJsonStructure(['html']);
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest')->get('/superadmin/operators')
            ->assertOk()->assertJsonStructure(['html', 'pagination']);
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest')->get('/superadmin/officers')
            ->assertOk()->assertJsonStructure(['html', 'pagination']);
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest')->get('/superadmin/presidents')
            ->assertOk()->assertJsonStructure(['html', 'pagination']);
    }

    public function test_officer_pages_searches_and_ajax_render()
    {
        $officer = $this->makeUser('tfrb_officer');
        $data = $this->seedOperators();
        $president = $this->makeUser('operator_president', $data['toda']);
        ActivityLog::create(['user_id' => $officer->id, 'category' => 'review', 'action' => 'review', 'description' => null]);

        $toda = $data['toda'];
        $active = $data['active'];

        $urls = [
            '/tfrb-officer/dashboard',
            '/tfrb-officer/operators',
            '/tfrb-officer/operators?search=' . urlencode($active->user->name) . '&status=bogus&account=bogus',
            '/tfrb-officer/operators?status=archived&account=inactive',
            '/tfrb-officer/operators?page=999',
            '/tfrb-officer/operators/create',
            '/tfrb-officer/operators/' . $active->id . '/edit',
            '/tfrb-officer/operators/' . $active->id . '/qrcode',
            '/tfrb-officer/ratings',
            '/tfrb-officer/ratings?date_from=bogus&date_to=bogus&operator_id=999999',
            '/tfrb-officer/complaints',
            '/tfrb-officer/complaints?filter=all',
            '/tfrb-officer/complaints?filter=bogus',
            '/tfrb-officer/reports',
            '/tfrb-officer/reports?toda_id=' . $toda->id . '&min_rating=bogus',
            '/tfrb-officer/invalid-ratings',
            '/tfrb-officer/activity-logs',
            '/tfrb-officer/activity-logs?category=review',
            '/tfrb-officer/activity-logs?category=bogus',
            '/tfrb-officer/todas',
            '/tfrb-officer/presidents',
            '/tfrb-officer/presidents?search=' . urlencode($president->name),
            '/tfrb-officer/settings',
            '/tfrb-officer/operators?search=' . urlencode('<script>alert(1)</script>'),
            '/notifications',
        ];

        foreach ($urls as $url) {
            $this->actingAs($officer)->get($url)->assertOk('Failed on: ' . $url);
        }

        $this->actingAs($officer)->getJson('/tfrb-officer/toda/' . $toda->id . '/members')
            ->assertOk()->assertJsonStructure(['html', 'count']);
        $this->actingAs($officer)->getJson('/tfrb-officer/reports/operators/' . $active->id . '/trips')
            ->assertOk()->assertJsonStructure(['html']);
        $this->actingAs($officer)->withHeader('X-Requested-With', 'XMLHttpRequest')->get('/tfrb-officer/operators')
            ->assertOk()->assertJsonStructure(['html', 'pagination']);
    }

    public function test_operator_search_filters_by_name_and_body_number()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('active');
        $operator->user->forceFill(['name' => 'SearchTargetName'])->save();
        $operator->forceFill(['body_number' => 'ZTARGETZ'])->save();

        $this->actingAs($admin)
            ->get('/superadmin/operators?search=SearchTargetName')
            ->assertOk()->assertSee('SearchTargetName');

        $this->actingAs($admin)
            ->get('/superadmin/operators?search=ZTARGETZ')
            ->assertOk()->assertSee('ZTARGETZ');
    }

    public function test_officer_search_filters_results()
    {
        $admin = $this->makeUser('superadmin');
        $match = $this->makeUser('tfrb_officer');
        $match->forceFill(['name' => 'UniqueOfficerZed'])->save();
        $other = $this->makeUser('tfrb_officer');
        $other->forceFill(['name' => 'SomeoneElse'])->save();

        $this->actingAs($admin)
            ->get('/superadmin/officers?search=UniqueOfficerZed')
            ->assertOk()->assertSee('UniqueOfficerZed');
    }

    public function test_toda_create_update_and_delete_flow()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->post('/superadmin/todas', ['name' => 'Audit TODA', 'area' => 'Caloocan'])
            ->assertRedirect(route('superadmin.todas'));

        $toda = Toda::where('name', 'Audit TODA')->firstOrFail();

        $this->actingAs($admin)
            ->put('/superadmin/todas/' . $toda->id, ['name' => 'Audit TODA Renamed', 'area' => 'Caloocan', 'is_active' => 1])
            ->assertRedirect(route('superadmin.todas'));
        $this->assertSame('Audit TODA Renamed', $toda->fresh()->name);

        $operator = $this->makeOperator('active', $toda);

        $this->actingAs($admin)
            ->delete('/superadmin/todas/' . $toda->id)
            ->assertRedirect();
        $this->assertDatabaseHas('todas', ['id' => $toda->id]);

        $operator->delete();

        $this->actingAs($admin)
            ->delete('/superadmin/todas/' . $toda->id)
            ->assertRedirect(route('superadmin.todas'));
        $this->assertDatabaseMissing('todas', ['id' => $toda->id]);
    }

    public function test_superadmin_can_create_and_delete_officer_account()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)->post('/superadmin/officers', [
            'name' => 'Audit Officer',
            'email' => 'audit.officer@example.com',
            'password' => 'password123',
            'phone' => '09171234567',
        ])->assertRedirect(route('superadmin.officers'));

        $officer = User::where('email', 'audit.officer@example.com')->firstOrFail();
        $this->assertSame('tfrb_officer', $officer->role);
        $this->assertTrue((bool) $officer->is_active);
        $this->assertNotNull($officer->email_verified_at);

        $this->actingAs($admin)
            ->delete('/superadmin/officers/' . $officer->id)
            ->assertRedirect(route('superadmin.officers'));
        $this->assertDatabaseMissing('users', ['id' => $officer->id]);
    }

    public function test_operator_can_change_own_password_from_settings()
    {
        $operator = $this->makeOperator('active');
        $user = $operator->user;

        $this->actingAs($user)->put('/operator/settings/password', [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_notifications_read_all_marks_everything_read()
    {
        $admin = $this->makeUser('superadmin');
        $first = Notification::create(['user_id' => $admin->id, 'type' => 'new_rating', 'title' => 'One', 'message' => 'x']);
        $second = Notification::create(['user_id' => $admin->id, 'type' => 'complaint', 'title' => 'Two', 'message' => 'y']);

        $this->actingAs($admin)->post('/notifications/read-all')->assertRedirect();

        $this->assertTrue((bool) $first->fresh()->is_read);
        $this->assertTrue((bool) $second->fresh()->is_read);
    }

    public function test_dashboards_render_with_no_data_at_all()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator('active');
        $president = $this->makeUser('operator_president', $this->makeToda());

        $adminUrls = [
            '/superadmin/dashboard',
            '/superadmin/operators',
            '/superadmin/ratings',
            '/superadmin/complaints',
            '/superadmin/reports',
            '/superadmin/invalid-ratings',
            '/superadmin/activity-logs',
            '/superadmin/todas',
            '/superadmin/presidents',
            '/superadmin/officers',
        ];
        foreach ($adminUrls as $url) {
            $this->actingAs($admin)->get($url)->assertOk('Empty superadmin page failed: ' . $url);
        }

        $officerUrls = [
            '/tfrb-officer/dashboard',
            '/tfrb-officer/operators',
            '/tfrb-officer/ratings',
            '/tfrb-officer/complaints',
            '/tfrb-officer/reports',
            '/tfrb-officer/invalid-ratings',
            '/tfrb-officer/activity-logs',
            '/tfrb-officer/todas',
            '/tfrb-officer/presidents',
        ];
        foreach ($officerUrls as $url) {
            $this->actingAs($officer)->get($url)->assertOk('Empty officer page failed: ' . $url);
        }

        foreach (['/operator/dashboard', '/operator/ratings', '/operator/profile', '/operator/settings'] as $url) {
            $this->actingAs($operator->user)->get($url)->assertOk('Empty operator page failed: ' . $url);
        }

        foreach (['/president/dashboard', '/president/members'] as $url) {
            $this->actingAs($president)->get($url)->assertOk('Empty president page failed: ' . $url);
        }

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/rate/' . $operator->qr_code)->assertOk();

        // President is allowed the shared notifications surface.
        $this->actingAs($president)->get('/notifications')->assertOk();
    }

    private function assertNoDuplicateIds(string $html, string $context): void
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $html);
        $html = preg_replace('#<!--.*?-->#s', '', (string) $html);

        preg_match_all('/\sid="([^"]+)"/', (string) $html, $matches);
        $duplicates = array_keys(array_filter(array_count_values($matches[1]), fn ($count) => $count > 1));

        $this->assertSame([], $duplicates, "Duplicate element id(s) on {$context}: " . implode(', ', $duplicates));
    }

    public function test_rendered_pages_do_not_contain_duplicate_element_ids()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president', $toda);
        $operator = $this->makeOperator('active', $toda);
        $this->makeRating($operator, 5, true, true);
        $this->makeOperator('active', $toda);

        $cases = [
            [$admin, '/superadmin/dashboard'],
            [$admin, '/superadmin/operators'],
            [$admin, '/superadmin/ratings'],
            [$admin, '/superadmin/complaints'],
            [$admin, '/superadmin/reports'],
            [$admin, '/superadmin/invalid-ratings'],
            [$admin, '/superadmin/activity-logs'],
            [$admin, '/superadmin/todas'],
            [$admin, '/superadmin/presidents'],
            [$admin, '/superadmin/officers'],
            [$admin, '/notifications'],
            [$officer, '/tfrb-officer/dashboard'],
            [$officer, '/tfrb-officer/operators'],
            [$officer, '/tfrb-officer/presidents'],
            [$officer, '/tfrb-officer/todas'],
            [$officer, '/tfrb-officer/ratings'],
            [$officer, '/tfrb-officer/complaints'],
            [$officer, '/tfrb-officer/reports'],
            [$operator->user, '/operator/dashboard'],
            [$operator->user, '/operator/ratings'],
            [$operator->user, '/operator/profile'],
            [$operator->user, '/operator/settings'],
            [$president, '/president/dashboard'],
            [$president, '/president/members'],
        ];

        foreach ($cases as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk('Page failed to render for duplicate-id audit: ' . $url);
            $this->assertNoDuplicateIds($response->getContent(), $url);
        }
    }

    public function test_president_member_detail_modal_paginates_within_json_payload()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president', $toda);
        $member = $this->makeOperator('active', $toda);

        // 12 ratings => more than one page at 10 per page.
        for ($i = 0; $i < 12; $i++) {
            $this->makeRating($member, 5);
        }

        $first = $this->actingAs($president)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/president/members/' . $member->id);
        $first->assertOk()->assertJsonStructure(['html']);
        $this->assertStringContainsString('data-president-pagination', $first->json('html'));

        $second = $this->actingAs($president)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/president/members/' . $member->id . '?page=2');
        $second->assertOk()->assertJsonStructure(['html']);
        $this->assertStringContainsString('data-president-pagination', $second->json('html'));

        // A member with a single page must not render pagination controls.
        $quiet = $this->makeOperator('active', $toda);
        $this->makeRating($quiet, 4);

        $single = $this->actingAs($president)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/president/members/' . $quiet->id);
        $single->assertOk()->assertJsonStructure(['html']);
        $this->assertStringNotContainsString('data-president-pagination', $single->json('html'));
    }

    public function test_operator_action_buttons_are_safe_for_names_with_quotes()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeOperator('active', $toda);
        $operator->user->forceFill(['name' => 'O\'Brien "The Boss" <script>'])->save();

        $response = $this->actingAs($admin)->get('/superadmin/operators');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('O\'Brien "The Boss" <script>', $html);

        preg_match('/data-operator-view=\'([^\']*)\'/', $html, $matches);
        $this->assertNotEmpty($matches, 'operator view payload missing');
        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'operator view payload is not valid JSON');
        $this->assertSame('O\'Brien "The Boss" <script>', $decoded['name']);

        $this->assertStringNotContainsString(
            "confirm('Reset the password of O'Brien",
            $html,
            'Reset-password confirm must not embed an unescaped name.'
        );
    }

    public function test_every_admin_get_page_renders_including_qrcode_and_settings()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $operator = $this->makeOperator('active', $toda);

        $cases = [
            [$admin, [
                '/superadmin/operators/create',
                '/superadmin/operators/' . $operator->id . '/edit',
                '/superadmin/operators/' . $operator->id . '/qrcode',
                '/superadmin/officers/create',
                '/superadmin/settings',
                '/superadmin/todas/create',
                '/superadmin/todas/' . $toda->id . '/edit',
            ]],
            [$officer, [
                '/tfrb-officer/operators/create',
                '/tfrb-officer/operators/' . $operator->id . '/edit',
                '/tfrb-officer/operators/' . $operator->id . '/qrcode',
                '/tfrb-officer/settings',
            ]],
        ];

        foreach ($cases as [$user, $urls]) {
            foreach ($urls as $url) {
                $response = $this->actingAs($user)->get($url);
                $response->assertOk('Admin GET page failed: ' . $url);
                $this->assertNoDuplicateIds($response->getContent(), $url);
            }
        }
    }

    public function test_complaints_can_be_filtered_by_one_and_two_stars()
    {
        $admin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $op = $this->makeOperator();

        $one = $this->makeRating($op, 1, true, true);
        $two = $this->makeRating($op, 2, true, true);
        $three = $this->makeRating($op, 3, true, true);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=all&rating=1')
            ->assertOk()
            ->assertSee($one->reference_number, false)
            ->assertDontSee($two->reference_number, false)
            ->assertDontSee($three->reference_number, false);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=all&rating=2')
            ->assertOk()
            ->assertSee($two->reference_number, false)
            ->assertDontSee($one->reference_number, false);

        $this->actingAs($officer)->get('/tfrb-officer/complaints?filter=all&rating=1')
            ->assertOk()
            ->assertSee($one->reference_number, false)
            ->assertDontSee($two->reference_number, false);

        // Unknown star values must fall back safely instead of erroring.
        $this->actingAs($admin)->get('/superadmin/complaints?filter=all&rating=999')
            ->assertOk()
            ->assertSee($one->reference_number, false);
    }

    public function test_complaints_keep_status_filter_when_star_filter_is_active()
    {
        $admin = $this->makeUser('superadmin');
        $op = $this->makeOperator();

        $pending = $this->makeRating($op, 1, true, true);
        $reviewed = $this->makeRating($op, 1, true, true);
        $reviewed->update(['is_reviewed' => true]);
        $two = $this->makeRating($op, 2, true, true);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=pending&rating=1')
            ->assertOk()
            ->assertSee($pending->reference_number, false)
            ->assertDontSee($reviewed->reference_number, false)
            ->assertDontSee($two->reference_number, false);
    }

    public function test_complaint_cards_show_a_reference_number()
    {
        $admin = $this->makeUser('superadmin');
        $op = $this->makeOperator();
        $rating = $this->makeRating($op, 1, true, true);

        $expected = sprintf('TFR-%d-%04d', (int) $rating->created_at->year, $rating->id);
        $this->assertSame($rating->reference_number, $expected);

        $this->actingAs($admin)->get('/superadmin/complaints?filter=all')
            ->assertOk()
            ->assertSee($expected, false)
            ->assertDontSee('#' . $rating->id, false);
    }

    public function test_complaints_export_contains_reference_number()
    {
        $admin = $this->makeUser('superadmin');
        $op = $this->makeOperator();
        $rating = $this->makeRating($op, 1, true, true);

        $this->actingAs($admin)->get('/superadmin/complaints/export?format=csv&filter=all')
            ->assertOk()
            ->assertSee('Reference', false)
            ->assertSee($rating->reference_number, false);
    }
}
