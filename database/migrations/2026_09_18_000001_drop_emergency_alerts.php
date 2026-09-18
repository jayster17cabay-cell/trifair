<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the passenger Emergency (SOS) feature entirely.
 *
 * The original create migrations (2026_09_09_000002 / 000003) were deleted
 * alongside the feature, so fresh databases never build the tables. This
 * migration exists to clean up production databases that already had them,
 * and is a no-op (guarded) on fresh databases.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('notifications', 'emergency_alert_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropConstrainedForeignId('emergency_alert_id');
            });
        }

        Schema::dropIfExists('emergency_alerts');
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('emergency_alert_id')->nullable()->after('rating_id')->constrained()->nullOnDelete();
        });

        if (!Schema::hasTable('emergency_alerts')) {
            Schema::create('emergency_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('passenger_id')->nullable()->index();
                $table->string('passenger_name')->nullable();
                $table->string('passenger_contact')->nullable();
                $table->foreignId('trip_id')->nullable()->constrained('ratings')->nullOnDelete();
                $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('toda_id')->nullable()->constrained()->nullOnDelete();
                $table->string('category', 40);
                $table->text('note')->nullable();
                $table->decimal('location_lat', 10, 7)->nullable();
                $table->decimal('location_lng', 10, 7)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('resolution_note')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
                $table->index(['toda_id', 'created_at']);
            });
        }
    }
};