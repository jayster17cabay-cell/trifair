<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspensionViolationProofsTable extends Migration
{
    public function up()
    {
        Schema::create('suspension_violation_proofs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('violation_id');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->foreign('violation_id')->references('id')->on('suspension_violations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('suspension_violation_proofs');
    }
}
