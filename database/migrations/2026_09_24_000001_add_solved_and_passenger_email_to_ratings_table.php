<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSolvedAndPassengerEmailToRatingsTable extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->string('passenger_email', 255)->nullable()->after('passenger_contact');
            $table->boolean('is_solved')->default(false)->after('is_reviewed');
            $table->timestamp('solved_at')->nullable()->after('is_solved');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn(['passenger_email', 'is_solved', 'solved_at']);
        });
    }
}