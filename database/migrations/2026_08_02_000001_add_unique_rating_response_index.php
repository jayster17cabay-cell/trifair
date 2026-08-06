<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUniqueRatingResponseIndex extends Migration
{
    /**
     * operator_responses has a hasOne relation to ratings, but nothing
     * enforced it — a duplicate response per rating was possible. Dedupe
     * existing rows (keep the earliest) and add a unique index.
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('DELETE r1 FROM operator_responses r1 INNER JOIN operator_responses r2 WHERE r1.id > r2.id AND r1.rating_id = r2.rating_id');
            DB::statement('ALTER TABLE operator_responses ADD UNIQUE INDEX operator_responses_rating_id_unique (rating_id)');
        } else {
            DB::statement('DELETE FROM operator_responses a USING operator_responses b WHERE a.id > b.id AND a.rating_id = b.rating_id');
            DB::statement('CREATE UNIQUE INDEX operator_responses_rating_id_unique ON operator_responses (rating_id)');
        }
    }

    public function down()
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE operator_responses DROP INDEX operator_responses_rating_id_unique');
        } else {
            DB::statement('DROP INDEX IF EXISTS operator_responses_rating_id_unique');
        }
    }
}
