<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateRoleAdminToTfrbOfficer extends Migration
{
    public function up()
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'tfrb_officer']);
    }

    public function down()
    {
        DB::table('users')->where('role', 'tfrb_officer')->update(['role' => 'admin']);
    }
}
