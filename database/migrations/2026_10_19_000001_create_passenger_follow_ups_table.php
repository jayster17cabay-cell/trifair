<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassengerFollowUpsTable extends Migration
{
    /**
     * A passenger's follow-up on one of their own complaints: an extra message
     * (additional context, a status nudge) attached to the rating so the TFRB
     * and superadmin queues can read it alongside the original complaint.
     */
    public function up()
    {
        Schema::create('passenger_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained('ratings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();

            $table->index(['rating_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('passenger_follow_ups');
    }
}
