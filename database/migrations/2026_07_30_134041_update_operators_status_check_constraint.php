<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateOperatorsStatusCheckConstraint extends Migration
{
    public function up()
    {
        // SQLite does not support ALTER TABLE ... DROP/ADD CONSTRAINT.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive', 'pending', 'rejected'))");
    }

    public function down()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive'))");
    }
}
