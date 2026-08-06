<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::rename('drivers', 'operators');
        Schema::rename('driver_responses', 'operator_responses');

        $db = DB::getDriverName();

        if ($db === 'pgsql') {
            DB::statement('ALTER TABLE ratings DROP CONSTRAINT IF EXISTS ratings_driver_id_foreign');
            DB::statement('ALTER TABLE ratings RENAME COLUMN driver_id TO operator_id');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_operator_id_foreign FOREIGN KEY (operator_id) REFERENCES operators(id) ON DELETE CASCADE');
        } elseif ($db === 'mysql') {
            DB::statement('ALTER TABLE ratings DROP FOREIGN KEY ratings_driver_id_foreign');
            DB::statement('ALTER TABLE ratings CHANGE driver_id operator_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_operator_id_foreign FOREIGN KEY (operator_id) REFERENCES operators(id) ON DELETE CASCADE');
        }
    }

    public function down()
    {
        $db = DB::getDriverName();

        if ($db === 'pgsql') {
            DB::statement('ALTER TABLE ratings DROP CONSTRAINT IF EXISTS ratings_operator_id_foreign');
            DB::statement('ALTER TABLE ratings RENAME COLUMN operator_id TO driver_id');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_driver_id_foreign FOREIGN KEY (driver_id) REFERENCES operators(id) ON DELETE CASCADE');
        } elseif ($db === 'mysql') {
            DB::statement('ALTER TABLE ratings DROP FOREIGN KEY ratings_operator_id_foreign');
            DB::statement('ALTER TABLE ratings CHANGE operator_id driver_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_driver_id_foreign FOREIGN KEY (driver_id) REFERENCES operators(id) ON DELETE CASCADE');
        }

        Schema::rename('operator_responses', 'driver_responses');
        Schema::rename('operators', 'drivers');
    }
};
