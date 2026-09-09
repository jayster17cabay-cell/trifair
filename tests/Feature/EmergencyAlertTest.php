<?php

namespace Tests\Feature;

use App\Models\EmergencyAlert;
use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmergencyAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?Toda $toda = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test ' . ucfirst($role) . ' ' . Str::random(4),
            'email' => $role . '_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
        ]);
        if ($toda) {
            $user->toda_id = $toda->id;
        }
        $user->save();

        return $user;
    }

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Solano',
            'is_active' => true,
        ]);
    }

    private function makeAlert(Toda $toda, array $overrides = []): EmergencyAlert
    {
        return EmergencyAlert::create(array_merge([
            'passenger_id' => 'device-x',
            'passenger_name' => 'Carlos',
            'toda_id' => $toda->id,
            'category' => 'accident',
            'location_lat' => 16.5185,
            'location_lng' => 121.1835,
            'status' => 'active',
        ], $overrides));
    }

    public function test_superadmin_sees_all_alerts()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $this->makeAlert($toda);
        $this->makeAlert($toda, ['category' => 'other']);

        $this->actingAs($admin)
            ->get('/superadmin/alerts')
            ->assertOk()
            ->assertSee('Emergency Alerts')
            ->assertSee('Accident')
            ->assertSee('Other');
    }

    public function test_president_alerts_are_scoped_to_own_toda()
    {
        $myToda = $this->makeToda();
        $otherToda = $this->makeToda();
        $president = $this->makeUser('operator_president', $myToda);
        $this->makeAlert($myToda, ['passenger_name' => 'MyPassenger']);
        $this->makeAlert($otherToda, ['passenger_name' => 'OtherPassenger']);

        $response = $this->actingAs($president)
            ->get('/president/alerts')
            ->assertOk();

        $response->assertSee('MyPassenger');
        $response->assertDontSee('OtherPassenger');
        $response->assertSee('TODA President');
    }

    public function test_president_cannot_update_another_todas_alert()
    {
        $myToda = $this->makeToda();
        $otherToda = $this->makeToda();
        $president = $this->makeUser('operator_president', $myToda);
        $alert = $this->makeAlert($otherToda);

        $this->actingAs($president)
            ->patchJson('/president/alerts/' . $alert->id, ['status' => 'responding'])
            ->assertNotFound();

        $this->assertSame('active', $alert->fresh()->status);
    }

    public function test_president_can_acknowledge_allow_then_resolve_own_alert()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president', $toda);
        $alert = $this->makeAlert($toda);

        $this->actingAs($president)
            ->patchJson('/president/alerts/' . $alert->id, ['status' => 'responding'])
            ->assertOk()
            ->assertJson(['status' => 'responding']);

        $this->actingAs($president)
            ->patchJson('/president/alerts/' . $alert->id, ['status' => 'resolved', 'resolution_note' => 'All safe'])
            ->assertOk()
            ->assertJson(['status' => 'resolved']);

        $alert->refresh();
        $this->assertSame('resolved', $alert->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertSame($president->id, (int) $alert->resolved_by);
        $this->assertSame('All safe', $alert->resolution_note);
    }

    public function test_resolved_alert_cannot_be_acknowledged_again()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president', $toda);
        $alert = $this->makeAlert($toda, ['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => $president->id]);

        $this->actingAs($president)
            ->patchJson('/president/alerts/' . $alert->id, ['status' => 'responding'])
            ->assertForbidden();
    }

    public function test_alert_json_polling_returns_counts_and_html()
    {
        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $this->makeAlert($toda);
        $this->makeAlert($toda, ['status' => 'responding']);

        $response = $this->actingAs($admin)
            ->getJson('/superadmin/alerts?json=1')
            ->assertOk()
            ->assertJsonStructure(['html', 'counts', 'signature', 'hasItems']);

        $data = $response->json();
        $this->assertSame(1, $data['counts']['active']);
        $this->assertSame(1, $data['counts']['responding']);
        $this->assertTrue($data['hasItems']);
        $this->assertStringContainsString('Accident', $data['html']);
    }

    public function test_officer_can_view_alerts()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $this->makeAlert($toda);

        $this->actingAs($officer)
            ->get('/tfrb-officer/alerts')
            ->assertOk()
            ->assertSee('Emergency Alerts');
    }
}