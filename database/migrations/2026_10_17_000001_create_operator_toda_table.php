<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TODA membership. An operator holds a single LGU franchise, but may be a
     * member of more than one TODA; each membership is one row here. This is
     * the source of truth for which TODAs an operator can be rated under — the
     * franchise is operator-wide and its expiry blocks every TODA at once.
     */
    public function up()
    {
        Schema::create('operator_toda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('toda_id')->constrained()->cascadeOnDelete();
            // 'active' = current member; 'inactive' = membership withdrawn but
            // retained for history.
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['operator_id', 'toda_id']);
            $table->index(['toda_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('operator_toda');
    }
};
