<?php

namespace Tests\Feature;

use App\Models\EmergencyAlert;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SosTest extends TestCase
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

    private function recipientIds(int $alertId): array
    {
        return Notification::where('emergency_alert_id', $alertId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function makeToda(): Toda
    {
        return Toda::create([
            'name' => 'Test TODA ' . Str::random(4),
            'area' => 'Solano',
            'is_active' => true,
        ]);
    }

    private function makeOperator(Toda $toda): Operator
    {
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

    public function test_anonymous_passenger_can_send_sos_and_all_responder_types_are_notified()
    {
        $superadmin = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $otherToda = $this->makeToda();
        $president = $this->makeUser('operator_president', $toda);
        $otherPresident = $this->makeUser('operator_president', $otherToda);
        $operator = $this->makeOperator($toda);

        $response = $this->postJson('/sos', [
            'category' => 'accident',
            'note' => 'Collision near the market',
            'passenger_id' => 'device-abc-123',
            'operator_id' => $operator->id,
            'passenger_name' => 'Maria Santos',
            'passenger_contact' => '09171234567',
            'location_lat' => 16.5185,
            'location_lng' => 121.1835,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $alert = EmergencyAlert::where('passenger_id', 'device-abc-123')->firstOrFail();
        $this->assertSame('accident', $alert->category);
        $this->assertSame('active', $alert->status);
        $this->assertEquals($operator->id, $alert->operator_id);
        $this->assertEquals($toda->id, $alert->toda_id);
        $this->assertSame('Maria Santos', $alert->passenger_name);

        // Superadmin, officer and ONLY the matched TODA's president get notified.
        $recipientIds = $this->recipientIds($alert->id);
        $this->assertContains($superadmin->id, $recipientIds);
        $this->assertContains($officer->id, $recipientIds);
        $this->assertContains($president->id, $recipientIds);
        $this->assertNotContains($otherPresident->id, $recipientIds);
        $this->assertSame(3, count($recipientIds));

        $presidentNotification = Notification::where('user_id', $president->id)->where('emergency_alert_id', $alert->id)->firstOrFail();
        $this->assertSame('emergency', $presidentNotification->type);
        $this->assertStringContainsString('Accident', $presidentNotification->title);
    }

    public function test_sos_without_operator_context_still_notifies_officers()
    {
        $superadmin = $this->makeUser('superadmin');
        $president = $this->makeUser('operator_president', $this->makeToda());

        $response = $this->postJson('/sos', [
            'category' => 'general',
        ]);

        $response->assertStatus(201);
        $alert = EmergencyAlert::firstOrFail();
        $this->assertNull($alert->operator_id);
        $this->assertNull($alert->toda_id);

        $recipientIds = $this->recipientIds($alert->id);
        $this->assertContains($superadmin->id, $recipientIds);
        $this->assertNotContains($president->id, $recipientIds);
    }

    public function test_client_cannot_set_toda_routing()
    {
        $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $otherToda = $this->makeToda();
        $presidentOfOtherToda = $this->makeUser('operator_president', $otherToda);
        $operator = $this->makeOperator($toda);

        // The anonymous client tells us it belongs to a TODA it should not
        // be allowed to route to (e.g. by random probing).
        $response = $this->postJson('/sos', [
            'category' => 'other',
            'operator_id' => $operator->id,
            'toda_id' => $otherToda->id,
        ]);

        $response->assertStatus(201);
        $alert = EmergencyAlert::firstOrFail();
        $this->assertEquals($toda->id, $alert->toda_id);

        $recipientIds = $this->recipientIds($alert->id);
        $this->assertNotContains($presidentOfOtherToda->id, $recipientIds);
    }

    public function test_sos_rejects_invalid_category()
    {
        $this->postJson('/sos', ['category' => 'spam', 'note' => 'x', 'category_extra' => 'x'])->assertStatus(422);
        $this->assertDatabaseCount('emergency_alerts', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_sos_is_rate_limited()
    {
        $this->makeUser('superadmin');

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/sos', ['category' => 'general'])->assertStatus(201);
        }

        $this->postJson('/sos', ['category' => 'general'])->assertStatus(429);
    }
}
