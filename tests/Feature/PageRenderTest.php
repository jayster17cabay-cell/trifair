<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\OperatorProof;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Page ' . ucfirst(str_replace('_', ' ', $role)),
            'email' => $role . '_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
        ]);
        $user->save();

        return $user;
    }

    private function op(): Operator
    {
        return Operator::create([
            'user_id' => $this->makeUser('operator')->id,
            'toda_id' => $this->makeToda()->id,
            'qr_code' => Str::random(32),
            'status' => 'active',
            'contact_number' => '09171234567',
            'license_number' => 'L-' . Str::random(6),
            'plate_number' => 'P-' . Str::random(6),
            'body_number' => 'B-' . Str::random(6),
        ]);
    }

    private function makeToda(): Toda
    {
        return Toda::create(['name' => 'Page TODA ' . Str::random(4), 'area' => 'Quezon City', 'is_active' => true]);
    }

    private function complaint(Operator $operator): Rating
    {
        $r = Rating::create([
            'operator_id' => $operator->id,
            'rating' => 2,
            'start_location' => 'a',
            'end_location' => 'b',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
            'complaint_type' => 'Rude Driver',
            'complaint_details' => 'details',
        ]);
        RatingProof::create([
            'rating_id' => $r->id,
            'file_path' => 'proofs/x.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'x.jpg',
        ]);
        $r->response()->create(['message' => 'my side']);
        OperatorProof::create([
            'rating_id' => $r->id,
            'file_path' => 'operator-proofs/' . $r->id . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $r;
    }

    public function test_tfrb_officer_presidents_pages_render()
    {
        $officer = $this->makeUser('tfrb_officer');
        $toda = $this->makeToda();
        $president = $this->makeUser('operator_president');
        $president->forceFill(['toda_id' => $toda->id])->save();

        $this->actingAs($officer)->get('/tfrb-officer/presidents')->assertOk();
        $this->actingAs($officer)->get('/tfrb-officer/presidents/create')->assertOk();
        $this->actingAs($officer)->get('/tfrb-officer/presidents?search=' . substr($president->name, 0, 4))->assertOk();
    }

    public function test_all_key_pages_render_without_error()
    {
        $super = $this->makeUser('superadmin');
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->op();
        $this->complaint($operator);

        $president = $this->makeUser('operator_president');
        $president->forceFill(['toda_id' => $operator->toda_id])->save();

        $toda = $operator->toda;

        $gets = [
            'superadmin' => [
                '/superadmin/dashboard',
                '/superadmin/operators',
                '/superadmin/operators?status=pending',
                '/superadmin/operators?status=archived',
                '/superadmin/operators?account=inactive',
                '/superadmin/operators/create',
                '/superadmin/operators/' . $operator->id . '/edit',
                '/superadmin/ratings',
                '/superadmin/complaints',
                '/superadmin/reports',
                '/superadmin/activity-logs',
                '/superadmin/presidents',
                '/superadmin/presidents/create',
                '/superadmin/todas',
                '/superadmin/todas/create',
                '/superadmin/todas/' . $toda->id . '/edit',
            ],
            'tfrb-officer' => [
                '/tfrb-officer/dashboard',
                '/tfrb-officer/operators',
                '/tfrb-officer/operators/create',
                '/tfrb-officer/operators/' . $operator->id . '/edit',
                '/tfrb-officer/ratings',
                '/tfrb-officer/complaints',
                '/tfrb-officer/reports',
                '/tfrb-officer/activity-logs',
                '/tfrb-officer/presidents',
                '/tfrb-officer/presidents/create',
                '/tfrb-officer/todas',
            ],
        ];

        foreach ($gets as $as => $urls) {
            $user = $as === 'superadmin' ? $super : $officer;
            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertStatus(200);
            }
        }

        foreach (['/operator/dashboard', '/operator/ratings', '/operator/settings', '/operator/profile'] as $url) {
            $this->actingAs($operator->user)->get($url)->assertOk();
        }

        foreach (['/president/dashboard', '/president/members', '/president/members/' . $operator->id] as $url) {
            $this->actingAs($president)->get($url)->assertOk();
        }

        foreach (['/', '/login'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/rate/' . $operator->qr_code)->assertOk();
    }
}