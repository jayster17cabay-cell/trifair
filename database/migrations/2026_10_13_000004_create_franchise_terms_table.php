<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Franchise terms: one row per franchise issuance/renewal for an operator.
     * The operator identity row is permanent; a renewal closes the current
     * term (status = ended) and opens a new active term, so the current view
     * starts back at zero while every past term is preserved for the
     * "All Time" view. Expiry is derived from valid_until, not stored, so no
     * scheduled job is required to expire a term.
     */
    public function up()
    {
        Schema::create('franchise_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('franchise_no')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            // 'active' = the operator's current term; 'ended' = superseded by a
            // later renewal. Plain string keeps the table portable to SQLite.
            $table->string('status')->default('active');
            $table->foreignId('renewed_from_id')->nullable()->constrained('franchise_terms')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['operator_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('franchise_terms');
    }
};
