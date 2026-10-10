<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time recovery codes for the OTP-based forgot-password flow.
     * The code is stored hashed; only the most recent code per email is kept.
     */
    public function up()
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_reset_codes');
    }
};