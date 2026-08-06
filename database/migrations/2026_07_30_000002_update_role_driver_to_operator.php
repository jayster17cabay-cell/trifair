<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('users')->where('role', 'driver')->update(['role' => 'operator']);
        DB::table('activity_logs')->where('category', 'driver')->update(['category' => 'operator']);
    }

    public function down()
    {
        DB::table('users')->where('role', 'operator')->update(['role' => 'driver']);
        DB::table('activity_logs')->where('category', 'operator')->update(['category' => 'driver']);
    }
};
