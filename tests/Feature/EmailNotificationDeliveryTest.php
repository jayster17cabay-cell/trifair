<?php

namespace Tests\Feature;

use App\Mail\ComplaintStatus;
use App\Mail\VerifyEmail;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression coverage for user-facing email delivery.
 *
 * Two things must never silently regress:
 *
 * 1. Every account gets a branded verification email (registration + resend)
 *    through the shared transactional pipeline, with both HTML and plain text.
 * 2. Solving, rejecting, escalating and forwarding a complaint always emails
 *    the passenger — no matter whether a TODA president, a TFRB officer or a
 *    superadmin performed the action, and no matter whether an earlier status
 *    email had already been delivered.
 */
class EmailNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const PASSENGER_EMAIL = 'passenger@example.com';

    private function makeUser(string $role, array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Test ' . ucfirst(str_replace('_', ' ', $role)),
            'email' => $role . '_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'phone' => '09171234567',
        ], $overrides));
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

    private function makePresident(Toda $toda): User
    {
        $user = $this->makeUser('operator_president');

        $operator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PRES-' . Str::random(4),
            'body_number' => 'P' . Str::random(4),
        ]);
        $operator->setTodaPresidency($toda->id, true);

        return $user;
    }

    private function makeMemberOperator(Toda $toda): Operator
    {
        $operator = Operator::create([
            'user_id' => $this->makeUser('operator')->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
        $operator->joinToda($toda->id);

        return $operator;
    }

    private function makeComplaint(Operator $operator, ?Toda $toda = null, array $overrides = []): Rating
    {
        return Rating::create(array_merge([
            'operator_id' => $operator->id,
            'toda_id' => $toda?->id,
            'rating' => 1,
            'complaint_type' => 'Rude Driver',
            'complaint_details' => 'Rude to passenger',
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'passenger_email' => self::PASSENGER_EMAIL,
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ], $overrides));
    }

    private function markStatusEmailAlreadySent(Rating $rating): void
    {
        $rating->forceFill([
            'complaint_status_email_sent_at' => now()->subMinute(),
        ])->saveQuietly();
    }

    // ---- verification email -------------------------------------------------

    public function test_registration_sends_branded_verification_email()
    {
        Mail::fake();

        $this->post('/register', [
            'first_name' => 'New',
            'last_name' => 'Operator',
            'email' => 'newop@example.com',
            'contact_number' => '09171234567',
            'plate_number' => 'PLATE-9001',
            'body_number' => 'BODY-9001',
            'motorcycle_model' => 'Honda Wave 125',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'newop@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(VerifyEmail::class, function (VerifyEmail $mail) use ($user) {
            // Mail::fake() stores the mailable before build() runs, so build it
            // here to inspect the configured HTML/text views.
            $mail->build();

            return $mail->hasTo($user->email)
                && $mail->view === 'emails.verify-email'
                && $mail->textView === 'emails.verify-email-text'
                && str_contains($mail->verificationUrl, '/email/verify/');
        });
    }

    public function test_resend_sends_verification_email_and_skips_verified_accounts()
    {
        Mail::fake();

        $unverified = $this->makeUser('operator', [
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($unverified)
            ->post('/email/resend')
            ->assertRedirect()
            ->assertSessionHas('message', 'Verification link sent!');

        Mail::assertSent(VerifyEmail::class, fn (VerifyEmail $mail) => $mail->hasTo('unverified@example.com'));
        Mail::assertSent(VerifyEmail::class, 1);

        // A verified account must not receive another verification message.
        $verified = $this->makeUser('passenger', ['email' => 'verified@example.com']);

        $this->actingAs($verified)
            ->post('/email/resend')
            ->assertRedirect()
            ->assertSessionHas('message', 'Your email address is already verified.');

        Mail::assertSent(VerifyEmail::class, 1);
    }

    // ---- solve emails across every acting role ------------------------------

    public function test_president_solve_emails_the_passenger()
    {
        Mail::fake();

        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda, ['routed_to' => $president->id]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/solve', [
                'resolution_note' => 'Driver was warned.',
            ])
            ->assertRedirect();

        Mail::assertSent(ComplaintStatus::class, function (ComplaintStatus $mail) {
            return $mail->hasTo(self::PASSENGER_EMAIL) && $mail->status === 'solved';
        });
    }

    public function test_president_reject_emails_the_passenger()
    {
        Mail::fake();

        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda, ['routed_to' => $president->id]);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/reject', [
                'resolution_note' => 'Unverifiable claim.',
            ])
            ->assertRedirect();

        Mail::assertSent(ComplaintStatus::class, function (ComplaintStatus $mail) {
            return $mail->hasTo(self::PASSENGER_EMAIL) && $mail->status === 'rejected';
        });
    }

    public function test_superadmin_solve_and_reject_emails_the_passenger()
    {
        Mail::fake();

        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeMemberOperator($toda);

        $solved = $this->makeComplaint($operator, $toda);
        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $solved->id . '/solve')
            ->assertRedirect();

        $rejected = $this->makeComplaint($operator, $toda);
        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $rejected->id . '/reject')
            ->assertRedirect();

        Mail::assertSent(ComplaintStatus::class, fn (ComplaintStatus $mail) => $mail->status === 'solved'
            && $mail->hasTo(self::PASSENGER_EMAIL));
        Mail::assertSent(ComplaintStatus::class, fn (ComplaintStatus $mail) => $mail->status === 'rejected'
            && $mail->hasTo(self::PASSENGER_EMAIL));
    }

    public function test_tfrb_officer_solve_and_reject_emails_the_passenger()
    {
        Mail::fake();

        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);

        // Forwarded cases are the ones the TFRB may act on.
        $solved = $this->makeComplaint($operator, $toda, [
            'routed_to' => $president->id,
            'forwarded_at' => now(),
            'forwarded_by' => $president->id,
        ]);
        $this->actingAs($officer)
            ->patch('/tfrb-officer/complaints/' . $solved->id . '/solve')
            ->assertRedirect();

        $rejected = $this->makeComplaint($operator, $toda, [
            'routed_to' => $president->id,
            'forwarded_at' => now(),
            'forwarded_by' => $president->id,
        ]);
        $this->actingAs($officer)
            ->patch('/tfrb-officer/complaints/' . $rejected->id . '/reject')
            ->assertRedirect();

        Mail::assertSent(ComplaintStatus::class, fn (ComplaintStatus $mail) => $mail->status === 'solved'
            && $mail->hasTo(self::PASSENGER_EMAIL));
        Mail::assertSent(ComplaintStatus::class, fn (ComplaintStatus $mail) => $mail->status === 'rejected'
            && $mail->hasTo(self::PASSENGER_EMAIL));
    }

    // ---- new status after an earlier status email ---------------------------

    public function test_escalation_emails_the_passenger_even_after_a_previous_status_email()
    {
        Mail::fake();

        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda, [
            'is_reviewed' => true,
            'is_solved' => true,
            'solved_at' => now(),
        ]);
        $this->markStatusEmailAlreadySent($complaint);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/escalate')
            ->assertRedirect();

        // If the old marker had not been cleared, notifyComplaintStatus() would
        // return early; a delivered escalation email proves the reset worked.
        Mail::assertSent(ComplaintStatus::class, function (ComplaintStatus $mail) {
            return $mail->hasTo(self::PASSENGER_EMAIL) && $mail->status === 'escalated';
        });
    }

    public function test_forward_emails_the_passenger_even_after_a_previous_status_email()
    {
        Mail::fake();

        $toda = $this->makeToda();
        $president = $this->makePresident($toda);
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda, ['routed_to' => $president->id]);
        $this->markStatusEmailAlreadySent($complaint);

        $this->actingAs($president)
            ->patch('/president/complaints/' . $complaint->id . '/forward')
            ->assertRedirect();

        // If the old marker had not been cleared, notifyComplaintStatus() would
        // return early; a delivered forward email proves the reset worked.
        Mail::assertSent(ComplaintStatus::class, function (ComplaintStatus $mail) {
            return $mail->hasTo(self::PASSENGER_EMAIL) && $mail->status === 'forwarded';
        });
    }

    public function test_reopen_then_solve_sends_a_second_status_email()
    {
        Mail::fake();

        $admin = $this->makeUser('superadmin');
        $toda = $this->makeToda();
        $operator = $this->makeMemberOperator($toda);
        $complaint = $this->makeComplaint($operator, $toda);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/solve')
            ->assertRedirect();
        Mail::assertSent(ComplaintStatus::class, 1);

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/reopen')
            ->assertRedirect();

        $this->assertNull(
            $complaint->fresh()->complaint_status_email_sent_at,
            'Reopening must clear the delivery marker for the next resolution cycle.'
        );

        $this->actingAs($admin)
            ->patch('/superadmin/complaints/' . $complaint->id . '/solve')
            ->assertRedirect();

        Mail::assertSent(ComplaintStatus::class, 2);
    }
}
