<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Accounts created by a TODA president, TFRB officer or superadmin start with
 * the shared default operator password and must replace it before they can
 * browse the rest of the application.
 */
class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeOperatorUser(bool $mustChange = true, string $status = 'active'): array
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test Operator',
            'email' => 'forced_' . Str::random(6) . '@example.com',
            'password' => Hash::make(User::DEFAULT_OPERATOR_PASSWORD),
            'email_verified_at' => now(),
            'role' => 'operator',
            'is_active' => true,
            'must_change_password' => $mustChange,
        ]);
        $user->save();

        $operator = Operator::create([
            'user_id' => $user->id,
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
            'motorcycle_model' => 'Honda Click 125i',
            'qr_code' => Str::random(32),
            'status' => $status,
        ]);

        return [$user, $operator];
    }

    public function test_flagged_account_is_redirected_to_the_forced_change_page()
    {
        [$user] = $this->makeOperatorUser();

        $this->actingAs($user)
            ->get('/operator/pending')
            ->assertRedirect(route('password.force'));
    }

    public function test_flagged_account_gets_423_for_json_requests()
    {
        [$user] = $this->makeOperatorUser();

        $this->actingAs($user)
            ->getJson('/operator/pending')
            ->assertStatus(423)
            ->assertJson(['message' => 'You must change your password before continuing.']);
    }

    public function test_flagged_account_can_open_the_forced_change_page()
    {
        [$user] = $this->makeOperatorUser();

        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk()
            ->assertSee('Choose a New Password');
    }

    public function test_forced_change_rejects_the_default_password()
    {
        [$user] = $this->makeOperatorUser();

        $this->actingAs($user)
            ->put('/password/change', [
                'new_password' => User::DEFAULT_OPERATOR_PASSWORD,
                'new_password_confirmation' => User::DEFAULT_OPERATOR_PASSWORD,
            ])
            ->assertSessionHasErrors('new_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_forced_change_saves_the_new_password_and_clears_the_flag()
    {
        [$user] = $this->makeOperatorUser();

        $this->actingAs($user)
            ->put('/password/change', [
                'new_password' => 'brand-new-secret',
                'new_password_confirmation' => 'brand-new-secret',
            ])
            ->assertRedirect(route('operator.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('brand-new-secret', $user->password));
    }

    public function test_unflagged_account_is_not_redirected()
    {
        [$user] = $this->makeOperatorUser(false);

        $this->actingAs($user)
            ->get('/operator/pending')
            ->assertRedirect(route('operator.dashboard'));
    }
}
