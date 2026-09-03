<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PresidentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'superadmin'): User
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

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_list_presidents()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $president->forceFill(['toda_id' => $toda->id])->save();

        $this->actingAs($admin)
            ->get('/superadmin/presidents')
            ->assertOk()
            ->assertSee($president->name)
            ->assertSee($toda->name);
    }

    public function test_superadmin_can_view_create_president_form()
    {
        $admin = $this->makeUser('superadmin');
        $this->actingAs($admin)
            ->get('/superadmin/presidents/create')
            ->assertOk()
            ->assertSee('TODA to Oversee');
    }

    public function test_superadmin_can_store_president()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();

        $this->actingAs($admin)
            ->post('/superadmin/presidents', [
                'name' => 'New President',
                'email' => 'president' . Str::random(4) . '@example.com',
                'password' => 'password123',
                'phone' => '09171234567',
                'toda_id' => $toda->id,
            ])
            ->assertRedirect(route('superadmin.presidents'));

        $president = User::where('role', 'operator_president')->first();
        $this->assertNotNull($president);
        $this->assertEquals($toda->id, (int) $president->toda_id);
        $this->assertNotNull($president->email_verified_at);

        // A president also gets an operator record so they can carry a rating.
        $operator = Operator::where('user_id', $president->id)->first();
        $this->assertNotNull($operator);
        $this->assertEquals($toda->id, (int) $operator->toda_id);
    }

    public function test_store_president_requires_toda()
    {
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->from(route('superadmin.presidents.create'))
            ->post('/superadmin/presidents', [
                'name' => 'New President',
                'email' => 'president' . Str::random(4) . '@example.com',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('toda_id');
    }

    public function test_superadmin_can_delete_president()
    {
        $admin = $this->makeUser('superadmin');
        $president = $this->makeUser('operator_president');

        $this->actingAs($admin)
            ->delete('/superadmin/presidents/' . $president->id)
            ->assertRedirect(route('superadmin.presidents'));

        $this->assertDatabaseMissing('users', ['id' => $president->id]);
    }

    public function test_non_superadmin_cannot_manage_presidents()
    {
        $toda = $this->makeToda();
        $operator = $this->makeUser('operator');
        Operator::create([
            'user_id' => $operator->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
        ]);

        $this->actingAs($operator)
            ->get('/superadmin/presidents')
            ->assertForbidden();
    }

    public function test_presidents_view_members_button_opens_modal_not_raw_json()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $president->forceFill(['toda_id' => $toda->id])->save();

        $html = $this->actingAs($admin)
            ->get('/superadmin/presidents')
            ->assertOk()
            ->getContent();

        // The modal and its handler are present on the page...
        $this->assertStringContainsString('id="todaModal"', $html);
        $this->assertStringContainsString('function showTodaMembers', $html);
        $this->assertStringContainsString('showTodaMembers(' . $toda->id . ',', $html);
        // ...and there must be NO direct anchor to the raw JSON endpoint.
        $this->assertStringNotContainsString('href="/superadmin/toda/' . $toda->id . '/members"', $html);
    }
}
