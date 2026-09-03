<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety migration: ensure users.toda_id exists even if the earlier
 * 2026_09_03_000002_add_toda_id_to_users_table migration was marked
 * "done" but never actually applied the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'toda_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('toda_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'toda_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['toda_id']);
                $table->dropColumn('toda_id');
            });
        }
    }
};
