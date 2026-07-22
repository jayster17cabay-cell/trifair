<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (config('database.default') === 'sqlite') {
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
