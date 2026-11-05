<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passenger violation reports carry the trip they were on (From → To, like
 * complaints) so the TFRB can see the direction the passenger was headed,
 * and an explicit free-text reason when the violation type is "Others".
 */
class AddRouteFieldsToSuspensionViolations extends Migration
{
    public function up()
    {
        Schema::table('suspension_violations', function (Blueprint $table) {
            $table->string('start_location', 500)->nullable()->after('location');
            $table->string('end_location', 500)->nullable()->after('start_location');
            $table->string('other_reason', 100)->nullable()->after('violation_type');
        });
    }

    public function down()
    {
        Schema::table('suspension_violations', function (Blueprint $table) {
            $table->dropColumn(['start_location', 'end_location', 'other_reason']);
        });
    }
}
