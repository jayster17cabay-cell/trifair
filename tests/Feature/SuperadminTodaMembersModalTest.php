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

class SuperadminTodaMembersModalTest extends TestCase
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

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makeOperator(Toda $toda, ?string $name = null): Operator
    {
        $user = $this->makeUser('operator');
        if ($name) {
            $user->forceFill(['name' => $name])->save();
        }
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

    public function test_superadmin_dashboard_renders_toda_members_modal()
    {
        $toda = $this->makeToda();
        $this->makeOperator($toda);
        $admin = $this->makeUser('superadmin');

        $html = $this->actingAs($admin)
            ->get('/superadmin/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="todaModal"', $html);
        $this->assertStringContainsString('function showTodaMembers', $html);
        $this->assertStringContainsString('data-members-url=', $html);
        $this->assertStringContainsString('superadmin/toda', $html);
        $this->assertStringContainsString('onclick="showTodaMembers(' . $toda->id . ',', $html);
    }

    public function test_superadmin_toda_members_endpoint_returns_json()
    {
        $toda = $this->makeToda();
        $opA = $this->makeOperator($toda, 'Alpha Member');
        $opB = $this->makeOperator($toda, 'Beta Member');
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk();

        $resp->assertJsonStructure(['html', 'count']);
        $this->assertEquals(2, $resp->json('count'));
        $this->assertStringContainsString('Alpha Member', $resp->json('html'));
        $this->assertStringContainsString('Beta Member', $resp->json('html'));
        $this->assertStringContainsString('data-member-item', $resp->json('html'));
    }

    public function test_superadmin_toda_members_endpoint_respects_archived()
    {
        $toda = $this->makeToda();
        $opA = $this->makeOperator($toda, 'Alpha Member');
        $opB = $this->makeOperator($toda, 'Beta Member');
        $opB->update(['archived_at' => now()]);
        $admin = $this->makeUser('superadmin');

        $resp = $this->actingAs($admin)
            ->getJson('/superadmin/toda/' . $toda->id . '/members')
            ->assertOk();

        $this->assertEquals(1, $resp->json('count'));
        $this->assertStringContainsString('Alpha Member', $resp->json('html'));
        $this->assertStringNotContainsString('Beta Member', $resp->json('html'));
    }
}
