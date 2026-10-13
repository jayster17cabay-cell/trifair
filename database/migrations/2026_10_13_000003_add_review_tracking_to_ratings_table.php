<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Review tracking for a complaint:
     *   - rejected_at records exactly when an officer rejected the complaint
     *     (the solved side already has solved_at);
     *   - reviewed_by records which user made the review decision so the
     *     passenger, admins and the email can show who handled it.
     *
     * Both are nullable so existing rows and the pending/accept states are
     * unaffected.
     */
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('resolution_note');
            $table->foreignId('reviewed_by')->nullable()->after('is_accepted');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'reviewed_by']);
        });
    }
};
