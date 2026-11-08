<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Appeals created before the appeal workflow was connected to the
        // staff queue must be re-opened as well, otherwise they remain hidden
        // in the solved/TODA-president bucket forever.
        DB::table('ratings')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('passenger_follow_ups')
                    ->whereColumn('passenger_follow_ups.rating_id', 'ratings.id')
                    ->where('passenger_follow_ups.is_appeal', true);
            })
            ->update([
                'is_solved' => false,
                'is_reviewed' => false,
                'rejected_at' => null,
                'routed_to' => null,
                'forwarded_at' => null,
                'forwarded_by' => null,
                'solved_at' => null,
                'resolution_at' => null,
            ]);
    }

    public function down(): void
    {
        // Appeal decisions are user actions and cannot be safely reconstructed.
    }
};
