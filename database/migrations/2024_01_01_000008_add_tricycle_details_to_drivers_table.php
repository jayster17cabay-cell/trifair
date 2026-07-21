<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('plate_number')->nullable()->after('contact_number');
            $table->string('body_number')->nullable()->after('plate_number');
            $table->string('tricycle_color')->nullable()->after('body_number');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['plate_number', 'body_number', 'tricycle_color']);
        });
    }
};
