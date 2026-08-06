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

class AuthzAjaxTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
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

    private function makeOperator(string $status = 'active', ?Toda $toda = null): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => ($toda ?? $this->makeToda())->id,
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

    public function test_guests_are_redirected_to_login_for_admin_routes()
    {
        $this->get('/superadmin/dashboard')->assertRedirect(route('login'));
        $this->get('/tfrb-officer/dashboard')->assertRedirect(route('login'));
        $this->get('/superadmin/operators')->assertRedirect(route('login'));
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_admin_routes()
    {
        $operator = $this->makeUser('operator');

        $this->actingAs($operator)->get('/superadmin/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/tfrb-officer/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/superadmin/operators')->assertForbidden();
        $this->actingAs($operator)->get('/notifications')->assertForbidden();
    }

    public function test_officer_cannot_access_superadmin_routes()
    {
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)->get('/superadmin/dashboard')->assertForbidden();
        $this->actingAs($officer)->get('/superadmin/officers')->assertForbidden();
        $this->actingAs($officer)->get('/superadmin/todas')->assertForbidden();
    }

    public function test_superadmin_cannot_access_operator_routes()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)->get('/operator/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/operator/ratings')->assertForbidden();
    }

    public function test_superadmin_operators_ajax_returns_table_html_and_pagination()
    {
        $admin = $this->makeUser('superadmin');
        $this->makeOperator();

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/operators?search=Test&status=active')
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);
    }

    public function test_officer_operators_ajax_returns_table_html_and_pagination()
    {
        $officer = $this->makeUser('tfrb_officer');
        $this->makeOperator();

        $this->actingAs($officer)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/tfrb-officer/operators?status=active')
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);
    }

    public function test_superadmin_report_trips_ajax_returns_html()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('active');
        $this->makeValidRating($operator);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/reports/operators/' . $operator->id . '/trips')
            ->assertOk()
            ->assertJsonStructure(['html']);
    }

    public function test_officer_report_trips_ajax_returns_html()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator('active');
        $this->makeValidRating($operator);

        $this->actingAs($officer)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/tfrb-officer/reports/operators/' . $operator->id . '/trips')
            ->assertOk()
            ->assertJsonStructure(['html']);
    }

    public function test_report_trips_for_pending_operator_is_404()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator('pending');

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/reports/operators/' . $operator->id . '/trips')
            ->assertNotFound();
    }

    public function test_toda_members_ajax_returns_members()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeOperator('active', $toda);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk()
            ->assertJsonPath('members.0.name', $operator->user->name);
    }

    public function test_officer_toda_members_ajax_returns_members()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $operator = $this->makeOperator('active', $toda);

        $this->actingAs($officer)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/tfrb-officer/toda/' . $toda->id . '/members')
            ->assertOk()
            ->assertJsonPath('members.0.name', $operator->user->name);
    }
}
