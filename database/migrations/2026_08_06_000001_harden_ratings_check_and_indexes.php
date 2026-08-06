<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();

        // 1) Enforce rating 1-5 at the database level (previously any integer
        //    could be stored, silently corrupting dashboard aggregates).
        if (in_array($driver, ['pgsql', 'mysql'], true)) {
            // Sanitize any out-of-range values first so the constraint can be added.
            DB::statement('UPDATE ratings SET rating = 5 WHERE rating > 5');
            DB::statement('UPDATE ratings SET rating = 1 WHERE rating < 1');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_rating_check CHECK (rating >= 1 AND rating <= 5)');
        }

        // 2) Fix the stale role default (was 'driver' since the first migration).
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'operator'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'operator'");
        }

        // 3) Missing indexes. Postgres does not auto-index foreign keys, so the
        //    FK columns need explicit indexes (MySQL auto-creates them and is
        //    skipped to avoid redundant indexes).
        if ($driver !== 'mysql') {
            Schema::table('operators', function (Blueprint $table) {
                $table->index('user_id');
            });

            Schema::table('rating_proofs', function (Blueprint $table) {
                $table->index('rating_id');
            });
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->index(['operator_id', 'is_valid', 'created_at']);
            $table->index(['is_valid', 'created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['category', 'created_at']);
        });
    }

    public function down()
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'mysql'], true)) {
            DB::statement('ALTER TABLE ratings DROP CONSTRAINT ratings_rating_check');
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'driver'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'driver'");
        }

        if ($driver !== 'mysql') {
            Schema::table('operators', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });

            Schema::table('rating_proofs', function (Blueprint $table) {
                $table->dropIndex(['rating_id']);
            });
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['operator_id', 'is_valid', 'created_at']);
            $table->dropIndex(['is_valid', 'created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['category', 'created_at']);
        });
    }
};
