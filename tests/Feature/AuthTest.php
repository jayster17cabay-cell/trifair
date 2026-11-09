<?php

namespace Tests\Feature;

use App\Models\OperatorToda;
use App\Models\Toda;
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
            'first_name' => 'New',
            'last_name' => 'Operator',
            'email' => 'newop@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-1001',
            'body_number' => 'BODY-1001',
            'motorcycle_model' => 'Honda Wave 125',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
            'is_active' => true,
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'newop@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Operator New', $user->name);
        $this->assertEquals('New', $user->first_name);
        $this->assertEquals('Operator', $user->last_name);
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
            'first_name' => 'Second',
            'last_name' => 'Operator',
            'email' => 'second@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-DUP',
            'body_number' => 'BODY-2002',
            'motorcycle_model' => 'Honda Wave 125',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('plate_number');
    }

    public function test_logout_and_rate_form_are_csrf_exempt_for_sessionless_browsers()
    {
        // Smartphone in-app browsers frequently drop/block the session cookie,
        // which used to throw 419 on the passenger Sign out POST. Verify only
        // the logout action (and the public rate form) are exempted, while
        // every other sensitive POST route keeps full CSRF protection.
        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);

        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $exempt = $property->getValue($middleware);

        $this->assertIsArray($exempt);
        $this->assertContains('logout', $exempt);
        $this->assertContains('rate/*', $exempt);
        $this->assertNotContains('login', $exempt);
        $this->assertNotContains('register', $exempt);
    }

    public function test_register_with_a_toda_waits_for_that_todas_president()
    {
        $toda = Toda::create([
            'name' => 'Registration TODA',
            'area' => 'Quezon City',
            'is_active' => true,
        ]);

        $this->post('/register', [
            'first_name' => 'Waiting',
            'last_name' => 'Operator',
            'email' => 'waiting@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'WAIT-1001',
            'body_number' => 'WAIT-2001',
            'motorcycle_model' => 'Honda Wave 125',
            'toda_id' => $toda->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'waiting@example.com')->first();
        $this->assertNotNull($user);

        $operator = $user->operator;
        $this->assertNotNull($operator);
        $this->assertSame('pending', $operator->status);
        $this->assertSame($toda->id, (int) $operator->toda_id);

        // The membership waits in the president's queue instead of being
        // active straight away (which would make the account rateable before
        // approval).
        $this->assertDatabaseHas('operator_toda', [
            'operator_id' => $operator->id,
            'toda_id' => $toda->id,
            'status' => OperatorToda::STATUS_PENDING,
        ]);
        $this->assertFalse($operator->getRateableTodas()->contains('id', $toda->id));
    }
}
