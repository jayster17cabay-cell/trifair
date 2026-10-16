<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('toda_id')->nullable()->after('franchise_term_id')->constrained()->nullOnDelete();
            $table->index(['operator_id', 'toda_id', 'created_at']);
        });

        DB::statement('UPDATE ratings SET toda_id = (SELECT toda_id FROM franchise_terms WHERE franchise_terms.id = ratings.franchise_term_id) WHERE toda_id IS NULL AND franchise_term_id IS NOT NULL');
        DB::statement('UPDATE ratings SET toda_id = (SELECT toda_id FROM operators WHERE operators.id = ratings.operator_id) WHERE toda_id IS NULL');
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['operator_id', 'toda_id', 'created_at']);
            $table->dropForeign(['toda_id']);
            $table->dropColumn('toda_id');
        });
    }
};
