<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The TODA president is now the sole approver for join requests: once the
     * president approves, the membership is active immediately and TFRB /
     * superadmin approval is no longer part of the flow.
     *
     * Memberships already past the president's stage (pending_tfrb,
     * pending_superadmin, legacy pending_admin) were therefore approved by
     * their president too, so activate them now instead of leaving them stuck
     * in a final-approval queue that no longer exists.
     */
    public function up()
    {
        DB::table('operator_toda')
            ->whereIn('status', ['pending_tfrb', 'pending_superadmin', 'pending_admin'])
            ->update([
                'status' => 'active',
                'joined_at' => DB::raw('COALESCE(joined_at, updated_at, created_at)'),
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        // Irreversible: activated rows cannot be told apart from memberships
        // that were already active before this migration.
    }
};
