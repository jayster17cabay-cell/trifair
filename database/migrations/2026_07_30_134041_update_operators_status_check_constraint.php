<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateOperatorsStatusCheckConstraint extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive', 'pending', 'rejected'))");
    }

    public function down()
    {
        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive'))");
    }
}
