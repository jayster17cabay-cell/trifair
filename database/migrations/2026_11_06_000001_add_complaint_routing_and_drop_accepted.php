<?php

use App\Models\Operator;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Complaints become a routed workflow instead of a free-for-all officer
 * decision:
 *
 *  1. Complaints are routed at creation — either to the TODA president of
 *     the complaint's TODA (ordinary members) or to TFRB / superadmin
 *     (complaints against a TODA president, unassigned operators, or a
 *     TODA without an assigned president yet). Stored as routed_to.
 *  2. A president who cannot resolve the complaint forwards it to the
 *     TFRB / superadmin (forwarded_at). That hand-off is permanent: the
 *     president keeps no action rights on the complaint afterwards.
 *  3. The "accepted" decision step is removed — a complaint now counts
 *     toward the operator's rating once it has been reviewed (is_reviewed);
 *     rejected complaints never count (rejected_at is not null).
 *
 * Legacy data: accepted and rejected complaints are both marked reviewed;
 * rejected ones keep their rejected_at marker so reports stay consistent.
 * Open (unsolved) legacy complaints are routed per the same rule used for
 * new complaints.
 */
class AddComplaintRoutingAndDropAccepted extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('routed_to')->nullable()->after('toda_id');
            $table->foreign('routed_to')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('forwarded_at')->nullable()->after('routed_to');
            $table->unsignedBigInteger('forwarded_by')->nullable()->after('forwarded_at');
            $table->foreign('forwarded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['routed_to', 'forwarded_at']);
            $table->index('rejected_at');
        });

        // Legacy: accepted and rejected complaints both become Reviewed.
        DB::statement('UPDATE ratings SET is_reviewed = true WHERE complaint_type IS NOT NULL AND is_accepted IS NOT NULL');
        DB::statement('UPDATE ratings SET rejected_at = COALESCE(rejected_at, updated_at) WHERE complaint_type IS NOT NULL AND is_accepted = false AND rejected_at IS NULL');

        // Route existing open complaints per the new rule. PHP loop keeps the
        // fallback logic (legacy home TODA, president-of-TODA look-up) shared
        // with the runtime classifier instead of duplicating it in SQL.
        $open = DB::table('ratings')
            ->whereNotNull('complaint_type')
            ->where('is_solved', false)
            ->select('id', 'operator_id', 'toda_id')
            ->get();

        $operators = Operator::with('user')
            ->whereIn('id', $open->pluck('operator_id')->unique())
            ->get()
            ->keyBy('id');

        $presidentByToda = [];
        $pivotRows = DB::table('operator_toda')
            ->where('operator_toda.is_president', true)
            ->where('operator_toda.status', 'active')
            ->join('operators', 'operators.id', '=', 'operator_toda.operator_id')
            ->select('operator_toda.id as pivot_id', 'operator_toda.toda_id', 'operators.user_id')
            ->orderBy('operator_toda.id')
            ->get();

        $pivotTodaIds = [];
        foreach ($pivotRows as $row) {
            $pivotTodaIds[(int) $row->toda_id] = true;
            // Newest presidency wins (mirrors the president-switcher behaviour).
            $presidentByToda[(int) $row->toda_id] = (int) $row->user_id;
        }

        // Legacy user.toda_id key is the fallback for presidencies recorded
        // before the is_president pivot flag existed.
        $legacyRows = DB::table('users')
            ->where('role', 'operator_president')
            ->whereNotNull('toda_id')
            ->select('id', 'toda_id')
            ->get();

        foreach ($legacyRows as $row) {
            if (!isset($pivotTodaIds[(int) $row->toda_id])) {
                $presidentByToda[(int) $row->toda_id] = (int) $row->id;
            }
        }

        foreach ($open as $row) {
            $assignee = null;

            $operator = $operators[$row->operator_id] ?? null;
            if ($operator && ($operator->user->role ?? null) !== 'operator_president') {
                $todaId = $row->toda_id;

                // Legacy rows predate ratings.toda_id: fall back to the
                // operator's home TODA when they carry no memberships.
                if (!$todaId && !$operator->todaMemberships()->exists()) {
                    $todaId = $operator->toda_id;
                }

                if ($todaId) {
                    $assignee = $presidentByToda[$todaId] ?? null;
                }
            }

            DB::table('ratings')->where('id', $row->id)->update(['routed_to' => $assignee]);
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('is_accepted');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->boolean('is_accepted')->nullable()->after('is_reviewed');
        });

        // Best-effort reverse mapping: Reviewed-and-not-rejected → accepted,
        // rejected stays marked via rejected_at.
        DB::statement('UPDATE ratings SET is_accepted = false WHERE complaint_type IS NOT NULL AND rejected_at IS NOT NULL');
        DB::statement('UPDATE ratings SET is_accepted = true WHERE complaint_type IS NOT NULL AND rejected_at IS NULL AND is_reviewed = true');

        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        Schema::table('ratings', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign(['routed_to']);
                $table->dropForeign(['forwarded_by']);
            }
            $table->dropColumn(['routed_to', 'forwarded_at', 'forwarded_by']);
        });
    }
}
