<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a user to a TODA. Primary use is the "operator_president" role:
 * each president governs exactly one TODA, and every president-scoped query
 * filters members by this id so a president can never see another TODA's data.
 * For regular operators the same link already lives on the operators table,
 * so this column is nullable and unused for them.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('toda_id')
                ->nullable()
                ->after('phone')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['toda_id']);
            $table->dropColumn('toda_id');
        });
    }
};
