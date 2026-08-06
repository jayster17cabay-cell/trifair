<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE operators MODIFY status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }
    }

    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE operators MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        }
    }
};
