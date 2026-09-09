<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();

            // Passenger identity. Passengers are anonymous: passenger_id holds the
            // existing signed tf_pid cookie value (device-scoped pseudo id), and
            // name/contact are optional fields the passenger may have typed on the
            // rate form before triggering the SOS.
            $table->string('passenger_id')->nullable()->index();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_contact')->nullable();

            // Ride context. The app has no dedicated trips table; when the SOS is
            // triggered from the rate page we snapshot the current rating context.
            $table->foreignId('trip_id')->nullable()->constrained('ratings')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            // toda_id is ALWAYS derived server-side from the operator; the client
            // can never set it (routing to the TODA president depends on it).
            $table->foreignId('toda_id')->nullable()->constrained()->nullOnDelete();

            $table->string('category', 40); // general | accident | theft_harassment | other
            $table->text('note')->nullable();

            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();

            $table->string('status', 20)->default('active'); // active | responding | resolved | false_alarm
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['toda_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('emergency_alerts');
    }
};