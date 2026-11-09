<?php

namespace Tests\Feature;

use App\Mail\ViolationStatus;
use App\Models\MemberSuspension;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\SuspensionViolation;
use App\Models\Toda;
use App\Models\User;
use App\Services\SuspensionViolationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            ->assertSee('Continue with Google')
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

    public function test_guests_cannot_file_and_are_asked_to_continue_with_google()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);

        $this->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Still picking up passengers at the terminal this morning.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('violation_type');

        $this->assertDatabaseCount('suspension_violations', 0);
    }

    public function test_signed_in_passenger_is_linked_and_report_carries_the_route()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $suspension = $this->suspend($member, $toda, $president);
        $passenger = $this->makeUser('passenger', 'Maria Santos');

        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Saw them waiting for passengers at the stop.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
            'start_location' => 'Municipal Hall, Solano',
            'end_location' => 'Market Road, Solano',
        ])->assertRedirect(route('rate.report.submitted', $member->qr_code));

        $this->assertDatabaseHas('suspension_violations', [
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $passenger->id,
            'suspension_id' => $suspension->id,
            'start_location' => 'Municipal Hall, Solano',
            'end_location' => 'Market Road, Solano',
            'status' => 'pending',
        ]);
    }

    public function test_others_violation_type_requires_a_reason()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);
        $passenger = $this->makeUser('passenger');

        // "Others" without a reason is rejected...
        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Others',
            'details' => 'They were not following the rules.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('other_reason');

        $this->assertDatabaseCount('suspension_violations', 0);

        // ...and accepted with one, which is stored on the report.
        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Others',
            'other_reason' => 'No plate number visible',
            'details' => 'Motorcycle had no visible plate, passenger refused the ride.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('rate.report.submitted', $member->qr_code));

        $this->assertDatabaseHas('suspension_violations', [
            'operator_id' => $member->id,
            'violation_type' => 'Others',
            'other_reason' => 'No plate number visible',
        ]);
    }

    public function test_filing_report_shows_it_in_the_passenger_dashboard()
    {
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $president);
        $passenger = $this->makeUser('passenger', 'Maria Santos');

        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Observed at the terminal during our ride.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($passenger)
            ->get('/passenger/dashboard?tab=violations')
            ->assertOk()
            ->assertSee('My Violation Reports')
            ->assertSee('VR-')
            ->assertSee($member->user->name);
    }

    public function test_passenger_is_notified_when_report_is_reviewed_and_solved()
    {
        Mail::fake();

        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $suspension = $this->suspend($member, $toda, $president);
        $passenger = $this->makeUser('passenger');
        $officer = $this->makeUser('tfrb_officer');

        $violation = SuspensionViolation::create([
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $passenger->id,
            'suspension_id' => $suspension->id,
            'violation_type' => 'Operating while suspended',
            'details' => 'Still operating at the terminal.',
            'incident_at' => now()->subHours(2),
            'status' => 'pending',
        ]);

        app(SuspensionViolationService::class)->markReviewed($violation, $officer->id, 'Looks valid');
        app(SuspensionViolationService::class)->markSolved($violation, $officer->id, [
            'resolution_note' => 'Fined and warned.',
            'penalty_imposed' => '₱2,000 fine',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $passenger->id,
            'type' => 'violation',
            'title' => 'Violation report reviewed',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $passenger->id,
            'type' => 'violation',
            'title' => 'Violation report solved',
        ]);

        Mail::assertSent(ViolationStatus::class, 2);
    }

    public function test_president_filed_reports_do_not_email_passengers()
    {
        Mail::fake();

        $toda = $this->makeToda();
        $pres = $this->makeUser('operator_president');
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $pres);
        $officer = $this->makeUser('tfrb_officer');

        $violation = SuspensionViolation::create([
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $pres->id,
            'violation_type' => 'Operating while suspended',
            'details' => 'Still operating at the terminal.',
            'incident_at' => now()->subHours(2),
            'status' => 'pending',
        ]);

        app(SuspensionViolationService::class)->markSolved($violation, $officer->id, [
            'resolution_note' => 'Penalized.',
        ]);

        Mail::assertNothingSent();
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
        $passenger = $this->makeUser('passenger');

        $this->actingAs($passenger)->post('/rate/' . $member->qr_code . '/report', [
            'violation_type' => 'Operating while suspended',
            'details' => 'Stale page submission.',
            'incident_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('rate.operator', $member->qr_code));

        $this->assertDatabaseCount('suspension_violations', 0);
    }
}
