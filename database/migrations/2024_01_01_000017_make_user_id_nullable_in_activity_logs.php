<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support MODIFY. Recreate the table with nullable user_id.
            Schema::dropIfExists('activity_logs_new');
            Schema::create('activity_logs_new', function (Blueprint $table) {
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

            // Copy data if table exists
            if (Schema::hasTable('activity_logs')) {
                DB::statement('INSERT OR IGNORE INTO activity_logs_new (id, user_id, action, description, model_type, model_id, ip_address, user_agent, category, created_at, updated_at) SELECT id, user_id, action, description, model_type, model_id, ip_address, user_agent, category, created_at, updated_at FROM activity_logs');
            }

            Schema::dropIfExists('activity_logs');
            Schema::rename('activity_logs_new', 'activity_logs');
            return;
        }
        DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NULL');
    }

    public function down()
    {
        if (config('database.default') === 'sqlite') {
            return;
        }
        DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NOT NULL');
    }
};
