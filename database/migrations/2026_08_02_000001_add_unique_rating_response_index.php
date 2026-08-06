<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueRatingResponseIndex extends Migration
{
    /**
     * operator_responses has a hasOne relation to ratings, but nothing
     * enforced it — a duplicate response per rating was possible. Dedupe
     * existing rows (keep the earliest) and add a unique index.
     */
    public function up()
    {
        // Portable dedupe: keep the earliest row per rating_id (works on
        // pgsql, mysql, and sqlite — avoids DELETE ... USING / joins).
        DB::statement('DELETE FROM operator_responses WHERE id NOT IN (SELECT MIN(id) FROM operator_responses GROUP BY rating_id)');

        Schema::table('operator_responses', function (Blueprint $table) {
            $table->unique('rating_id');
        });
    }

    public function down()
    {
        Schema::table('operator_responses', function (Blueprint $table) {
            $table->dropUnique(['rating_id']);
        });
    }
}
