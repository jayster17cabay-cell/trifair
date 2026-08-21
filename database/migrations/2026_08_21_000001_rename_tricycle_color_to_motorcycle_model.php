<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('operators', 'tricycle_color')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE operators RENAME COLUMN tricycle_color TO motorcycle_model');
        } else {
            Schema::table('operators', function (Blueprint $table) {
                $table->renameColumn('tricycle_color', 'motorcycle_model');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('operators', 'motorcycle_model')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE operators RENAME COLUMN motorcycle_model TO tricycle_color');
        } else {
            Schema::table('operators', function (Blueprint $table) {
                $table->renameColumn('motorcycle_model', 'tricycle_color');
            });
        }
    }
};
