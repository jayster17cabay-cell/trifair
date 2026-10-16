<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('franchise_terms', function (Blueprint $table) {
            $table->foreignId('toda_id')->nullable()->after('operator_id')->constrained()->nullOnDelete();
            $table->index(['operator_id', 'toda_id', 'status']);
        });

        // Backfill: copy toda_id from operators
        DB::statement('UPDATE franchise_terms SET toda_id = (SELECT toda_id FROM operators WHERE operators.id = franchise_terms.operator_id) WHERE toda_id IS NULL');

        Schema::table('franchise_terms', function (Blueprint $table) {
            $table->index(['toda_id', 'status']);
        });
    }

    public function down()
    {
        Schema::table('franchise_terms', function (Blueprint $table) {
            $table->dropIndex(['toda_id', 'status']);
            $table->dropIndex(['operator_id', 'toda_id', 'status']);
            $table->dropForeign(['toda_id']);
            $table->dropColumn('toda_id');
        });
    }
};
