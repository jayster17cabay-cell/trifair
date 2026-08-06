<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Invalid: no location data
        DB::statement("
            UPDATE ratings
            SET is_valid = false
            WHERE start_location IS NULL
            AND end_location IS NULL
        ");

        // Invalid: complaint (<=2) without proof
        DB::statement("
            UPDATE ratings
            SET is_valid = false
            WHERE rating <= 2
            AND id NOT IN (
                SELECT DISTINCT rating_id FROM rating_proofs
            )
        ");
    }

    public function down()
    {
    }
};
