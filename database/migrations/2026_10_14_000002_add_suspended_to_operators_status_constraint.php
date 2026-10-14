<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allow 'suspended' as an operator status so a TODA president can suspend a
     * member. The extra state sits alongside active/inactive/pending/rejected.
     */
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE operators MODIFY status VARCHAR(20) NOT NULL DEFAULT 'active'");
            return;
        }

        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive', 'pending', 'rejected', 'suspended'))");
    }

    public function down()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE operators DROP CONSTRAINT IF EXISTS drivers_status_check');
        DB::statement("ALTER TABLE operators ADD CONSTRAINT drivers_status_check CHECK (status IN ('active', 'inactive', 'pending', 'rejected'))");
    }
};
