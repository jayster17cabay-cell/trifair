<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViolationFollowUpsTable extends Migration
{
    public function up()
    {
        Schema::create('violation_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('violation_id')->constrained('suspension_violations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->string('sender_role', 20)->default('passenger');
            $table->timestamps();

            $table->index(['violation_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('violation_follow_ups');
    }
}