<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('ratings', function (Blueprint $table) {
                $table->index('operator_id');
            });
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->index('is_valid');
            $table->index('rating');
            $table->index('is_reviewed');
            $table->index('complaint_type');
            $table->index(['is_valid', 'rating']);
            $table->index('created_at');
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['is_valid']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['is_reviewed']);
            $table->dropIndex(['complaint_type']);
            $table->dropIndex(['is_valid', 'rating']);
            $table->dropIndex(['created_at']);
            if (DB::getDriverName() !== 'mysql') {
                $table->dropIndex(['operator_id']);
            }
        });
    }
};
