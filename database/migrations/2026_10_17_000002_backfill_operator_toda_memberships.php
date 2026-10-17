<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed memberships from the pre-refactor data so nobody loses the TODAs
     * they could previously be rated under:
     *  - every operator's home TODA (operators.toda_id), and
     *  - every distinct TODA that appeared on their franchise terms (the old
     *    proxy for multi-TODA membership).
     */
    public function up()
    {
        $now = now();

        // Home TODA -> membership.
        $home = DB::table('operators')
            ->whereNotNull('toda_id')
            ->select('id', 'toda_id', 'created_at')
            ->get();

        foreach ($home as $row) {
            DB::table('operator_toda')->insertOrIgnore([
                'operator_id' => $row->id,
                'toda_id' => $row->toda_id,
                'status' => 'active',
                'joined_at' => $row->created_at ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Extra TODAs previously implied by franchise terms.
        $termTodas = DB::table('franchise_terms')
            ->whereNotNull('toda_id')
            ->select('operator_id', 'toda_id')
            ->distinct()
            ->get();

        foreach ($termTodas as $row) {
            DB::table('operator_toda')->insertOrIgnore([
                'operator_id' => $row->operator_id,
                'toda_id' => $row->toda_id,
                'status' => 'active',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table('operator_toda')->delete();
    }
};
