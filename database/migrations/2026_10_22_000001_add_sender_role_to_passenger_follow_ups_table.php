<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSenderRoleToPassengerFollowUpsTable extends Migration
{
    /**
     * The follow-up thread is now a chat between the passenger and the
     * TFRB/superadmin reviewers, so each message records who sent it:
     * 'passenger' for the rider, 'staff' for an officer/superadmin update.
     * Existing rows were all written by passengers.
     */
    public function up()
    {
        Schema::table('passenger_follow_ups', function (Blueprint $table) {
            $table->string('sender_role', 20)->default('passenger')->after('message');
        });
    }

    public function down()
    {
        Schema::table('passenger_follow_ups', function (Blueprint $table) {
            $table->dropColumn('sender_role');
        });
    }
}
