<?php

namespace Tests\Feature;

use App\Models\MemberSuspension;
use App\Models\Operator;
use App\Models\SuspensionViolation;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PassengerViolationReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $name = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $name ?? 'Test ' . ucfirst(str_replace('_', ' ', $role)),
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
            'area' => 'Solano, Nueva Vizcaya',
            'is_active' => true,
        ]);
    }

    private function makeOperator(Toda $toda, string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator', 'Operator ' . Str::upper(Str::random(4)));
        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
        $operator->joinToda($toda->id);

        return $operator;
    }

    private function suspend(Operator $member, Toda $toda, User $president): MemberSuspension
    {
        return $member->suspend([
            'toda_id' => $toda->id,
            'suspended_by' => $president->id,
            'reason' => 'Repeated overcharging',
            'suspended_at' => now()->subDays(2),
            'suspended_until' => now()->addDays(5),
        ]);
    }

    public function test_suspended_operator_qr_shows_report_screen_instead_of_stars()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);

        $this->get('/rate/' . $member->qr_code)
            ->assertOk()
            ->assertSee('Report Operator')
            ->assertSee('Report a Violation')
            ->assertSee('reportMap', false)
            ->assertDontSee('starGrid', false);
    }

    public function test_inactive_operator_qr_shows_report_screen()
    {
        $toda = $this->makeToda();
        $member = $this->makeOperator($toda, 'inactive');

        $this->get('/rate/' . $member->qr_code)
            ->assertOk()
            ->assertSee('Report Operator')
            ->assertDontSee('starGrid', false);
    }

    public function test_guest_can_file_a_violation_report_and_operator_is_attached()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $suspension = $this->suspend($member, $toda, $president);

        $this->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Still picking up passengers at the terminal this morning.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
            'location' => 'Market Road',
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => '09171234567',
        ])->assertRedirect(route('rate.report.submitted', $member->qr_code));

        $this->assertDatabaseHas('suspension_violations', [
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => null,
            'reporter_name' => 'Juan Dela Cruz',
            'suspension_id' => $suspension->id,
            'status' => 'pending',
        ]);
    }

    public function test_guest_must_leave_a_name_and_contact()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);

        $this->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Observed operating despite suspension.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors(['reporter_name', 'reporter_contact']);

        $this->assertDatabaseCount('suspension_violations', 0);
    }

    public function test_signed_in_passenger_is_linked_and_needs_no_guest_fields()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);
        $passenger = $this->makeUser('passenger', 'Maria Santos');

        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Saw them waiting for passengers at the stop.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('rate.report.submitted', $member->qr_code));

        $this->assertDatabaseHas('suspension_violations', [
            'operator_id' => $member->id,
            'reported_by' => $passenger->id,
        ]);
    }

    public function test_operator_role_accounts_cannot_file_reports()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);

        $this->actingAs($member->user)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Trying to file against myself.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('violation_type');

        $this->assertDatabaseCount('suspension_violations', 0);
    }

    public function test_report_is_rejected_when_operator_is_active_again()
    {
        $toda = $this->makeToda();
        $member = $this->makeOperator($toda, 'active');

        $this->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Stale page submission.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => '09171234567',
        ])->assertRedirect(route('rate.operator', $member->qr_code));

        $this->assertDatabaseCount('suspension_violations', 0);
    }
}
