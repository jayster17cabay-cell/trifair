<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->string('client_id', 64)->nullable()->after('passenger_ip');
            $table->index(['operator_id', 'client_id'], 'ratings_operator_client_idx');
            $table->index(['operator_id', 'passenger_ip'], 'ratings_operator_ip_idx');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex('ratings_operator_client_idx');
            $table->dropIndex('ratings_operator_ip_idx');
            $table->dropColumn('client_id');
        });
    }
};
