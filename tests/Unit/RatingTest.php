<?php

namespace Tests\Unit;

use App\Models\Rating;
use PHPUnit\Framework\TestCase;

class RatingTest extends TestCase
{
    public function test_normalize_address_returns_null_for_empty_values()
    {
        $this->assertNull(Rating::normalizeAddress(null));
        $this->assertNull(Rating::normalizeAddress(''));
    }

    public function test_normalize_address_strips_region_noise()
    {
        $cleaned = Rating::normalizeAddress('Quirino Highway, Novaliches, Quezon City, 1100, Philippines');
        $this->assertStringNotContainsString('Philippines', $cleaned);
        $this->assertStringNotContainsString('1100', $cleaned);
        $this->assertStringContainsString('Quirino Highway', $cleaned);
        $this->assertStringContainsString('Quezon City', $cleaned);
    }

    public function test_normalize_address_removes_empty_parts()
    {
        $cleaned = Rating::normalizeAddress('  Sampaloc  , , Manila, ');
        $this->assertEquals('Sampaloc, Manila', $cleaned);
    }

    public function test_normalize_address_caps_at_five_parts()
    {
        $cleaned = Rating::normalizeAddress('A, B, C, D, E, F, G');
        $this->assertCount(5, explode(',', $cleaned));
    }

    public function test_normalize_address_is_case_insensitive_for_skip_words()
    {
        $cleaned = Rating::normalizeAddress('Luzon, Manila');
        $this->assertStringNotContainsString('Luzon', $cleaned);
    }

    public function test_padded_complaint_stats_includes_all_types_even_without_matches()
    {
        $stats = collect([
            (object) ['complaint_type' => 'Overcharging', 'total' => 3],
        ]);

        $padded = Rating::paddedComplaintStats($stats);

        $types = $padded->pluck('complaint_type')->all();
        $this->assertContains('Overcharging', $types);
        $this->assertContains('Reckless driving', $types);
        $this->assertEquals(3, $padded->firstWhere('complaint_type', 'Overcharging')->total);
        $this->assertEquals(0, $padded->firstWhere('complaint_type', 'Rude Driver')->total);
    }

    public function test_padded_complaint_stats_sorts_descending_by_total()
    {
        $stats = collect([
            (object) ['complaint_type' => 'Overcharging', 'total' => 1],
            (object) ['complaint_type' => 'Rude Driver', 'total' => 5],
        ]);

        $padded = Rating::paddedComplaintStats($stats);

        $this->assertEquals(5, $padded->first()->total);
    }

    public function test_evaluate_validity_requires_both_locations()
    {
        $rating = new Rating(['rating' => 5, 'start_location' => 'A', 'end_location' => null]);
        $rating->setRelation('proofs', collect());

        $this->assertFalse($rating->evaluateValidity());
    }

    public function test_evaluate_validity_high_rating_without_proof_is_valid()
    {
        $rating = new Rating(['rating' => 5, 'start_location' => 'A', 'end_location' => 'B']);
        $rating->setRelation('proofs', collect());

        $this->assertTrue($rating->evaluateValidity());
    }

    public function test_evaluate_validity_low_rating_requires_proof()
    {
        $rating = new Rating(['rating' => 2, 'start_location' => 'A', 'end_location' => 'B']);
        $rating->setRelation('proofs', collect());

        $this->assertFalse($rating->evaluateValidity());

        $rating->setRelation('proofs', collect([(object) ['id' => 1]]));
        $this->assertTrue($rating->evaluateValidity());
    }
}
