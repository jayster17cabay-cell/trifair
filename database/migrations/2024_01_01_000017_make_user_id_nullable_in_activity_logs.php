<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE activity_logs MODIFY user_id BIGINT(20) UNSIGNED NOT NULL');
    }
};
