<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Member suspensions: one row per suspension issued against an operator by
     * their TODA president. Every record is kept as history — ending a
     * suspension stamps ended_at instead of deleting the row, so past
     * suspensions remain visible to the president, superadmin and TFRB officer.
     */
    public function up()
    {
        Schema::create('member_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('toda_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            // The resolution explaining why the member was suspended.
            $table->text('reason');
            $table->date('suspended_at');
            $table->date('suspended_until');
            // Stamped when the suspension ends — early (by the president) or
            // automatically once suspended_until has passed.
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['operator_id', 'ended_at']);
            $table->index(['ended_at', 'suspended_until']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_suspensions');
    }
};
