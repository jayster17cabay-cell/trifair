<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorResponseProofsTable extends Migration
{
    public function up()
    {
        Schema::create('operator_response_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 100)->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->index('rating_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('operator_response_proofs');
    }
}