<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\SuspensionViolation;
use App\Models\SuspensionViolationProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuspensionViolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'operator', ?string $name = null): User
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
            'area' => 'Quezon City',
            'is_active' => true,
        ]);
    }

    private function makePresident(Toda $toda): array
    {
        $user = $this->makeUser('operator_president');
        $user->forceFill(['toda_id' => $toda->id])->save();

        $ownOperator = Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'plate_number' => 'PRESIDENT',
            'body_number' => 'P01',
        ]);

        return ['user' => $user, 'operator' => $ownOperator];
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

    private function suspend(Operator $member, Toda $toda, User $president): \App\Models\MemberSuspension
    {
        return $member->suspend([
            'toda_id' => $toda->id,
            'suspended_by' => $president->id,
            'reason' => 'Repeated overcharging',
            'suspended_at' => now()->subDays(2),
            'suspended_until' => now()->addDays(5),
        ]);
    }

    private function makeViolation(Operator $member, Toda $toda, User $president, array $overrides = []): SuspensionViolation
    {
        return SuspensionViolation::create(array_merge([
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $president->id,
            'violation_type' => 'Operating while suspended',
            'details' => 'Still operating at the terminal despite suspension.',
            'incident_at' => now()->subHours(3),
            'location' => 'Market Road',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_president_can_report_a_violation_for_a_suspended_member()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $suspension = $this->suspend($member, $toda, $pres['user']);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/violation', [
                'violation_type' => 'Operating while suspended',
                'details' => 'Observed picking up passengers near the market.',
                'incident_at' => now()->format('Y-m-d H:i:s'),
                'location' => 'Market Road',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('suspension_violations', [
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $pres['user']->id,
            'suspension_id' => $suspension->id,
            'violation_type' => 'Operating while suspended',
            'status' => 'pending',
        ]);
    }

    public function test_president_can_attach_proofs_to_a_violation_report()
    {
        config(['services.supabase.url' => null, 'services.supabase.key' => null]);
        Storage::fake('public');

        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $pres['user']);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/violation', [
                'violation_type' => 'Operating while suspended',
                'details' => 'With photo evidence.',
                'incident_at' => now()->format('Y-m-d H:i:s'),
                'proofs' => [UploadedFile::fake()->create('evidence.jpg', 100, 'image/jpeg')],
            ])
            ->assertRedirect();

        $violation = SuspensionViolation::where('operator_id', $member->id)->first();
        $this->assertNotNull($violation);
        $this->assertSame(1, $violation->proofs()->count());
    }

    public function test_report_is_rejected_when_member_is_not_suspended()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/violation', [
                'violation_type' => 'Operating while suspended',
                'details' => 'Not suspended though.',
                'incident_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('suspension_violations', 0);
    }

    public function test_report_validates_required_fields()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $this->suspend($member, $toda, $pres['user']);

        $this->actingAs($pres['user'])
            ->post('/president/members/' . $member->id . '/violation', [])
            ->assertSessionHasErrors(['violation_type', 'details', 'incident_at']);
    }

    public function test_president_cannot_report_violation_for_another_todas_member()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $presA = $this->makePresident($todaA);
        $memberB = $this->makeOperator($todaB);
        $this->suspend($memberB, $todaB, $presA['user']);

        $this->actingAs($presA['user'])
            ->post('/president/members/' . $memberB->id . '/violation', [
                'violation_type' => 'Operating while suspended',
                'details' => 'Foreign TODA.',
                'incident_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertForbidden();
    }

    public function test_tfrb_officer_can_view_the_violations_queue()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $this->makeViolation($member, $toda, $pres['user'], ['details' => 'Unique violation marker ABCXYZ']);

        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->get('/tfrb-officer/violations')
            ->assertOk()
            ->assertSee('Violation Reports', false)
            ->assertSee('Unique violation marker ABCXYZ', false);
    }

    public function test_tfrb_officer_can_review_a_violation()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user']);
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->patch('/tfrb-officer/violations/' . $violation->id . '/review', [
                'review_note' => 'Confirmed with the TODA president.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $violation->refresh();
        $this->assertTrue($violation->is_reviewed);
        $this->assertFalse($violation->is_solved);
        $this->assertSame('reviewed', $violation->status);
        $this->assertSame($officer->id, $violation->reviewed_by);
        $this->assertSame('Confirmed with the TODA president.', $violation->review_note);
    }

    public function test_tfrb_officer_can_solve_a_violation_with_a_penalty()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user']);
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->patch('/tfrb-officer/violations/' . $violation->id . '/solve', [
                'resolution_note' => 'Penalty imposed and confirmed.',
                'recommended_action' => 'Additional 30-day suspension',
                'penalty_imposed' => 'PHP 2,000 fine + 30-day suspension',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $violation->refresh();
        $this->assertTrue($violation->is_solved);
        $this->assertTrue($violation->is_reviewed);
        $this->assertSame('solved', $violation->status);
        $this->assertNotNull($violation->solved_at);
        $this->assertSame('PHP 2,000 fine + 30-day suspension', $violation->penalty_imposed);
        $this->assertSame('Additional 30-day suspension', $violation->recommended_action);
    }

    public function test_tfrb_officer_can_reopen_a_solved_violation()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user'], [
            'is_reviewed' => true,
            'is_solved' => true,
            'status' => 'solved',
            'solved_at' => now(),
        ]);
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->patch('/tfrb-officer/violations/' . $violation->id . '/reopen')
            ->assertRedirect()
            ->assertSessionHas('success');

        $violation->refresh();
        $this->assertFalse($violation->is_solved);
        $this->assertFalse($violation->is_reviewed);
        $this->assertSame('pending', $violation->status);
        $this->assertNull($violation->solved_at);
    }

    public function test_superadmin_can_view_and_solve_a_violation()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user'], ['details' => 'Superadmin marker QQQ']);
        $admin = $this->makeUser('superadmin');

        $this->actingAs($admin)
            ->get('/superadmin/violations')
            ->assertOk()
            ->assertSee('Superadmin marker QQQ', false);

        $this->actingAs($admin)
            ->patch('/superadmin/violations/' . $violation->id . '/solve', [
                'resolution_note' => 'Resolved by superadmin.',
                'penalty_imposed' => 'Franchise revoked',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($violation->fresh()->is_solved);
    }

    public function test_solve_requires_a_resolution_note()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user']);
        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->patch('/tfrb-officer/violations/' . $violation->id . '/solve', [])
            ->assertSessionHasErrors('resolution_note');

        $this->assertFalse($violation->fresh()->is_solved);
    }

    public function test_guest_is_redirected_from_the_violations_queue()
    {
        $this->get('/tfrb-officer/violations')->assertRedirect('/login');
    }

    public function test_president_sees_their_reported_violations_in_the_member_modal()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $this->makeViolation($member, $toda, $pres['user'], ['details' => 'President-visible report marker ZZZ']);

        $html = $this->actingAs($pres['user'])
            ->getJson('/president/members/' . $member->id)
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Reported violations', $html);
        $this->assertStringContainsString('President-visible report marker ZZZ', $html);
    }

    public function test_violation_proofs_survive_a_reopen()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $violation = $this->makeViolation($member, $toda, $pres['user'], [
            'is_reviewed' => true,
            'is_solved' => true,
            'status' => 'solved',
            'solved_at' => now(),
        ]);
        SuspensionViolationProof::create([
            'violation_id' => $violation->id,
            'file_path' => 'violation-proofs/' . $violation->id . '/x.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'x.jpg',
        ]);

        $officer = $this->makeUser('tfrb_officer');

        $this->actingAs($officer)
            ->patch('/tfrb-officer/violations/' . $violation->id . '/reopen')
            ->assertRedirect();

        // The original evidence must survive a reopen.
        $this->assertDatabaseCount('suspension_violation_proofs', 1);
    }
}
