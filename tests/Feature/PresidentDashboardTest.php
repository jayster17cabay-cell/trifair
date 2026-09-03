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

class PresidentDashboardTest extends TestCase
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

    private function makeOperator(Toda $toda, string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator', 'Operator ' . Str::upper(Str::random(4)));
        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $toda->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
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
            'license_number' => 'LIC-PRES-' . Str::random(4),
            'plate_number' => 'PRESIDENT',
            'body_number' => 'P01',
        ]);

        return ['user' => $user, 'operator' => $ownOperator];
    }

    private function makeValidRating(Operator $operator, int $stars = 5, ?string $complaintType = null): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'rating' => $stars,
            'complaint_type' => $complaintType,
            'complaint_details' => $complaintType ? 'Complaint details here' : null,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    public function test_president_can_access_dashboard()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);

        $this->makeValidRating($member, 4);

        $this->actingAs($pres['user'])
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee($member->user->name)
            ->assertSee('Members');
    }

    public function test_non_president_cannot_access_dashboard()
    {
        $toda = $this->makeToda();
        $operator = $this->makeOperator($toda);

        $this->actingAs($operator->user)
            ->get('/president/dashboard')
            ->assertForbidden();

        $superadmin = $this->makeUser('superadmin');
        $this->actingAs($superadmin)
            ->get('/president/dashboard')
            ->assertForbidden();
    }

    public function test_president_only_sees_own_toda_members()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $pres = $this->makePresident($todaA);

        $memberA = $this->makeOperator($todaA);
        $memberB = $this->makeOperator($todaB);

        $this->actingAs($pres['user'])
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee($memberA->user->name)
            ->assertDontSee($memberB->user->name);
    }

    public function test_president_can_view_own_toda_member_detail()
    {
        $toda = $this->makeToda();
        $pres = $this->makePresident($toda);
        $member = $this->makeOperator($toda);
        $this->makeValidRating($member, 5);

        $this->actingAs($pres['user'])
            ->get('/president/members/' . $member->id)
            ->assertOk()
            ->assertSee($member->user->name);
    }

    public function test_president_cannot_view_other_toda_member_detail()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();
        $pres = $this->makePresident($todaA);
        $memberB = $this->makeOperator($todaB);

        $this->actingAs($pres['user'])
            ->get('/president/members/' . $memberB->id)
            ->assertForbidden();
    }

    public function test_summary_stats_are_scoped_to_own_toda()
    {
        $todaA = $this->makeToda();
        $todaB = $this->makeToda();

        $pres = $this->makePresident($todaA);

        $memberA1 = $this->makeOperator($todaA);
        $memberA2 = $this->makeOperator($todaA);
        $memberB = $this->makeOperator($todaB);

        $this->makeValidRating($memberA1, 5);
        $this->makeValidRating($memberA1, 3, 'Overcharging');
        $this->makeValidRating($memberA2, 4);
        // Other TODA data must NOT leak into the president's figures.
        $this->makeValidRating($memberB, 1, 'Reckless driving');

        $this->actingAs($pres['user'])
            ->get('/president/dashboard')
            ->assertOk()
            ->assertSee('Members');
    }

    public function test_guest_redirects_to_login()
    {
        $this->get('/president/dashboard')->assertRedirect(route('login'));
    }
}


