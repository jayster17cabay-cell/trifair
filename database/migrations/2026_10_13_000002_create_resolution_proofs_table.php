<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidence attached by the reviewing officer when a complaint is marked
     * solved (e.g. photo of the resolution). Kept separate from rating_proofs
     * (passenger evidence) and operator_response_proofs (operator evidence).
     */
    public function up()
    {
        Schema::create('resolution_proofs', function (Blueprint $table) {
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
        Schema::dropIfExists('resolution_proofs');
    }
};
