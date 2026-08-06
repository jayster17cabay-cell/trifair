<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('qr_code')->unique();
            $table->string('qr_code_path')->nullable();
            // Plain string (not enum): keeps the table portable to SQLite.
            // The active/inactive/pending/rejected CHECK is re-added on
            // pgsql/mysql by 2026_07_30_134041_update_operators_status_check_constraint.
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
