<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passenger_follow_ups', function (Blueprint $table) {
            $table->boolean('is_appeal')->default(false)->after('sender_role');
        });
    }

    public function down(): void
    {
        Schema::table('passenger_follow_ups', function (Blueprint $table) {
            $table->dropColumn('is_appeal');
        });
    }
};
