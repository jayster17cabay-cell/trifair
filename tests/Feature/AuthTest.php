<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'superadmin', array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Test ' . ucfirst($role),
            'email' => $role . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
        ], $overrides));
        $user->save();

        return $user;
    }

    public function test_login_with_mixed_case_email_succeeds()
    {
        $this->makeUser('superadmin', ['email' => 'superadmin@example.com']);

        $response = $this->post('/login', [
            'email' => 'SuperAdmin@Example.COM',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'superadmin@example.com')->first());
    }

    public function test_login_with_wrong_password_fails()
    {
        $this->makeUser('superadmin');

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_user_cannot_login()
    {
        $this->makeUser('superadmin', ['is_active' => false]);

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_six_attempts()
    {
        $this->makeUser('superadmin');

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'superadmin@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_register_cannot_escalate_role()
    {
        $this->post('/register', [
            'name' => 'New Operator',
            'email' => 'newop@example.com',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-1001',
            'plate_number' => 'PLATE-1001',
            'body_number' => 'BODY-1001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
            'is_active' => true,
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'newop@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('operator', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_register_rejects_duplicate_plate_number()
    {
        $this->makeUser('operator');
        \App\Models\Operator::create([
            'user_id' => User::where('role', 'operator')->first()->id,
            'plate_number' => 'PLATE-DUP',
            'body_number' => 'BODY-ORIG',
            'qr_code' => 'qrcode-original',
            'status' => 'active',
        ]);

        $this->post('/register', [
            'name' => 'Second Operator',
            'email' => 'second@example.com',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-2002',
            'plate_number' => 'PLATE-DUP',
            'body_number' => 'BODY-2002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('plate_number');
    }
}
