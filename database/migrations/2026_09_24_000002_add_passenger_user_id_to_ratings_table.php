<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassengerUserIdToRatingsTable extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('passenger_user_id')->nullable()->after('passenger_email');
            $table->foreign('passenger_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('passenger_user_id');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['passenger_user_id']);
            $table->dropIndex(['passenger_user_id']);
            $table->dropColumn('passenger_user_id');
        });
    }
}