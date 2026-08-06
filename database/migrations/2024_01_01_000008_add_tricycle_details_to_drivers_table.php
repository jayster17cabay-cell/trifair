<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('plate_number')->nullable();
            $table->string('body_number')->nullable();
            $table->string('tricycle_color')->nullable();
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['plate_number', 'body_number', 'tricycle_color']);
        });
    }
};
