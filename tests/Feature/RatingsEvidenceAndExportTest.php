<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RatingsEvidenceAndExportTest extends TestCase
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

    private function makeOperator(): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $this->makeToda()->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    public function test_app_timezone_is_asia_manila()
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', now()->timezoneName);
    }

    public function test_new_rating_timestamp_is_stored_in_philippine_time()
    {
        $operator = $this->makeOperator();

        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 5,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => true,
            'is_auto' => false,
            'complaint_type' => null,
        ]);

        $this->assertSame('Asia/Manila', $rating->created_at->timezoneName);
        $this->assertContains($rating->created_at->format('T'), ['PST', '+08:00']);
    }

    public function test_stored_utc_wall_clock_shifts_eight_hours_to_philippine_time()
    {
        // A passenger submitted a complaint at 15:00 PH time, which the old UTC
        // timezone stored as 07:00 (8 hours behind). The data correction must
        // bring it back forward to the correct local evidence time.
        $storedUtcWallClock = '2026-09-01 07:00:00';

        $philippineTime = Carbon::parse($storedUtcWallClock)->addHours(8)->format('Y-m-d H:i');

        $this->assertSame('2026-09-01 15:00', $philippineTime);
    }

    public function test_superadmin_can_export_ratings_with_evidence_timestamp()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();

        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 4,
            'comment' => 'Smooth and safe ride.',
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => true,
            'is_auto' => false,
            'complaint_type' => null,
        ]);

        $this->actingAs($admin)
            ->get('/superadmin/ratings/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee($operator->user->name, false)
            ->assertSee($rating->created_at->format('Y-m-d H:i'), false);
    }

    public function test_officer_can_export_ratings_with_evidence_timestamp()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();

        $rating = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 3,
            'comment' => 'Okay but late.',
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => true,
            'is_auto' => false,
            'complaint_type' => null,
        ]);

        $this->actingAs($officer)
            ->get('/tfrb-officer/ratings/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee($operator->user->name, false)
            ->assertSee($rating->created_at->format('Y-m-d H:i'), false);
    }
}
