<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspensionViolationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suspension_violations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('toda_id')->nullable();
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('suspension_id')->nullable();
            $table->string('violation_type');
            $table->text('details');
            $table->dateTime('incident_at');
            $table->string('location')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_reviewed')->default(false);
            $table->boolean('is_solved')->default(false);
            $table->dateTime('solved_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('resolution_note')->nullable();
            $table->string('recommended_action')->nullable();
            $table->text('penalty_imposed')->nullable();
            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('toda_id')->references('id')->on('todas')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('suspension_id')->references('id')->on('member_suspensions')->nullOnDelete();

            $table->index(['operator_id', 'status']);
            $table->index(['toda_id', 'status']);
            $table->index('reported_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suspension_violations');
    }
}
