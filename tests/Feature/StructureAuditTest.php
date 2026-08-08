<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StructureAuditTest extends TestCase
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

    private function makeOperator(string $status = 'active'): Operator
    {
        $user = $this->makeUser('operator');

        return Operator::create([
            'user_id' => $user->id,
            'toda_id' => $this->makeToda()->id,
            'qr_code' => Str::random(32),
            'status' => $status,
            'contact_number' => '09171234567',
            'license_number' => 'LIC-' . Str::random(6),
            'plate_number' => 'PLATE-' . Str::random(6),
            'body_number' => 'BODY-' . Str::random(6),
        ]);
    }

    private function makeValidRating(Operator $operator, int $stars = 5): Rating
    {
        return Rating::create([
            'operator_id' => $operator->id,
            'rating' => $stars,
            'start_location' => 'Novaliches, Quezon City',
            'end_location' => 'SM Fairview, Quezon City',
            'is_valid' => true,
            'is_reviewed' => false,
            'is_auto' => false,
        ]);
    }

    private function makeValidComplaint(Operator $operator): Rating
    {
        $rating = $this->makeValidRating($operator, 2);

        RatingProof::create([
            'rating_id' => $rating->id,
            'file_path' => 'proofs/' . $operator->qr_code . '/proof.jpg',
            'file_type' => 'image/jpeg',
            'original_name' => 'proof.jpg',
        ]);

        return $rating;
    }

    private function audit(string $path): void
    {
        $res = $this->get($path);
        $this->assertTrue($res->status() === 200, "FAIL status={$res->status()} on {$path}");

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $res->getContent());
        $pairs = [
            ['<div', '</div>', 'div'],
            ['<form', '</form>', 'form'],
            ['<table', '</table>', 'table'],
            ['<ul', '</ul>', 'ul'],
            ['<ol', '</ol>', 'ol'],
        ];
        foreach ($pairs as [$openTag, $closeTag, $name]) {
            $open = substr_count($html, $openTag);
            $close = substr_count($html, $closeTag);
            $this->assertSame($open, $close, "UNBALANCED {$name}: open={$open} close={$close} on {$path}");
        }
    }

    public function test_public_pages_structure()
    {
        foreach (['/', '/login', '/register', '/password/reset'] as $path) {
            $this->audit($path);
        }
        $this->assertTrue(true);
    }

    public function test_superadmin_pages_structure()
    {
        $admin = $this->makeUser('superadmin');
        $operator = $this->makeOperator();
        $toda = $operator->toda;
        $this->makeValidRating($operator);
        $this->makeValidComplaint($operator);
        $this->makeUser('tfrb_officer');
        ActivityLog::create(['user_id' => $admin->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        $this->actingAs($admin);
        foreach ([
            '/superadmin/dashboard',
            '/superadmin/operators',
            '/superadmin/operators/create',
            '/superadmin/operators/' . $operator->id . '/edit',
            '/superadmin/operators/' . $operator->id . '/qrcode',
            '/superadmin/officers',
            '/superadmin/officers/create',
            '/superadmin/complaints',
            '/superadmin/ratings',
            '/superadmin/reports',
            '/superadmin/activity-logs',
            '/superadmin/todas',
            '/superadmin/todas/create',
            '/superadmin/todas/' . $toda->id . '/edit',
            '/superadmin/invalid-ratings',
            '/superadmin/settings',
            '/notifications',
        ] as $path) {
            $this->audit($path);
        }
        $this->assertTrue(true);
    }

    public function test_officer_pages_structure()
    {
        $officer = $this->makeUser('tfrb_officer');
        $operator = $this->makeOperator();
        $this->makeValidRating($operator);
        $this->makeValidComplaint($operator);
        ActivityLog::create(['user_id' => $officer->id, 'category' => 'auth', 'action' => 'login', 'description' => null]);

        $this->actingAs($officer);
        foreach ([
            '/tfrb-officer/dashboard',
            '/tfrb-officer/operators',
            '/tfrb-officer/operators/create',
            '/tfrb-officer/operators/' . $operator->id . '/edit',
            '/tfrb-officer/operators/' . $operator->id . '/qrcode',
            '/tfrb-officer/complaints',
            '/tfrb-officer/ratings',
            '/tfrb-officer/reports',
            '/tfrb-officer/activity-logs',
            '/tfrb-officer/invalid-ratings',
            '/tfrb-officer/settings',
            '/tfrb-officer/todas',
            '/notifications',
        ] as $path) {
            $this->audit($path);
        }
        $this->assertTrue(true);
    }

    public function test_operator_pages_structure()
    {
        $operator = $this->makeOperator();
        $this->makeValidRating($operator);
        $operator->user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($operator->user);
        foreach ([
            '/operator/dashboard',
            '/operator/ratings',
            '/operator/profile',
            '/operator/settings',
        ] as $path) {
            $this->audit($path);
        }

        $pending = $this->makeOperator('pending');
        $this->actingAs($pending->user)->get('/operator/pending')->assertOk();
        $this->audit('/operator/pending');
        $this->assertTrue(true);
    }

    public function test_rate_pages_structure()
    {
        $operator = $this->makeOperator();
        $this->audit('/rate/' . $operator->qr_code);
        $this->get('/rate/' . $operator->qr_code)->assertOk();
        $this->assertTrue(true);
    }
}
