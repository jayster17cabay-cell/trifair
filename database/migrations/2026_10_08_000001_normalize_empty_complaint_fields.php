<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The rate form always posts complaint_type/complaint_details, even when
     * the complaint box is hidden (star ratings 3-5). Empty strings were stored
     * as literal '' rows, so every positive rating looked like a "complaint"
     * to the whereNotNull(complaint_type) scopes and vanished from the Ratings
     * page. Blank them out so only real complaints are classified as such.
     *
     * Validity rules are intentionally unchanged here: a rating still needs a
     * From/To trip (route) to count, matching Rating::evaluateValidity().
     */
    public function up()
    {
        // Empty-string complaints are not complaints.
        DB::statement("UPDATE ratings SET complaint_type = NULL WHERE complaint_type = ''");
        DB::statement("UPDATE ratings SET complaint_details = NULL WHERE complaint_details = ''");
    }

    public function down()
    {
    }
};