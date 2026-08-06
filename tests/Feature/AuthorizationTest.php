<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationTest extends TestCase
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

    private function makeActiveOperator(): Operator
    {
        $toda = Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);

        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/superadmin/dashboard')->assertRedirect('/login');
    }

    public function test_superadmin_can_open_superadmin_dashboard()
    {
        $this->actingAs($this->makeUser('superadmin'))
            ->get('/superadmin/dashboard')
            ->assertStatus(200);
    }

    public function test_superadmin_can_open_officer_dashboard()
    {
        $this->actingAs($this->makeUser('superadmin'))
            ->get('/tfrb-officer/dashboard')
            ->assertStatus(200);
    }

    public function test_superadmin_is_blocked_from_operator_dashboard()
    {
        $this->actingAs($this->makeUser('superadmin'))
            ->get('/operator/dashboard')
            ->assertStatus(403);
    }

    public function test_operator_is_blocked_from_notifications()
    {
        $operator = $this->makeActiveOperator();

        $this->actingAs($operator->user)
            ->get('/notifications')
            ->assertStatus(403);
    }

    public function test_operator_is_blocked_from_superadmin_dashboard()
    {
        $operator = $this->makeActiveOperator();

        $this->actingAs($operator->user)
            ->get('/superadmin/dashboard')
            ->assertStatus(403);
    }

    public function test_officer_is_blocked_from_superadmin_dashboard()
    {
        $this->actingAs($this->makeUser('tfrb_officer'))
            ->get('/superadmin/dashboard')
            ->assertStatus(403);
    }

    public function test_active_operator_can_open_own_dashboard()
    {
        $operator = $this->makeActiveOperator();

        $this->actingAs($operator->user)
            ->get('/operator/dashboard')
            ->assertStatus(200);
    }

    public function test_pending_operator_is_redirected_from_dashboard()
    {
        $user = $this->makeUser('operator');
        $toda = Toda::create(['name' => 'Pending TODA', 'area' => 'QC', 'is_active' => true]);
        Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get('/operator/dashboard')
            ->assertRedirect(route('operator.pending'));
    }
}
