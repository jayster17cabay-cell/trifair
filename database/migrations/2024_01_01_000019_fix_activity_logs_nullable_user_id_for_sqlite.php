<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        // Recreate activity_logs with nullable user_id for SQLite
        Schema::dropIfExists('activity_logs_fixed');
        Schema::create('activity_logs_fixed', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('category')->default('system');
            $table->timestamps();
        });

        if (Schema::hasTable('activity_logs')) {
            DB::statement('INSERT INTO activity_logs_fixed (id, action, description, model_type, model_id, ip_address, user_agent, category, created_at, updated_at) SELECT id, action, description, model_type, model_id, ip_address, user_agent, COALESCE(category, \'system\'), created_at, updated_at FROM activity_logs');
            Schema::dropIfExists('activity_logs');
        }

        Schema::rename('activity_logs_fixed', 'activity_logs');
    }

    public function down()
    {
        //
    }
};
