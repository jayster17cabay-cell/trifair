<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            return;
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE activity_logs ALTER COLUMN user_id DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NULL');
        }
    }

    public function down()
    {
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            return;
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE activity_logs ALTER COLUMN user_id SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NOT NULL');
        }
    }
};
