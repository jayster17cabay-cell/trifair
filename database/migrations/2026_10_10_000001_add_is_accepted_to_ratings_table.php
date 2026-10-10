<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsAcceptedToRatingsTable extends Migration
{
    /**
     * Introduce the complaint review decision.
     *
     * is_accepted is a nullable tri-state:
     *   NULL  -> pending review (officer has not decided yet)
     *   true  -> accepted (the 1-2 star complaint counts toward the operator)
     *   false -> rejected (fake/not a real complaint; excluded from averages)
     *
     * Complaints that were already reviewed/solved before this change keep
     * counting: backfill them as accepted. Only undecided complaints now need
     * an explicit Accept or Reject from the officer.
     */
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->boolean('is_accepted')->nullable()->after('is_valid');
        });

        DB::table('ratings')
            ->whereNotNull('complaint_type')
            ->where('is_reviewed', true)
            ->update(['is_accepted' => true]);
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('is_accepted');
        });
    }
}