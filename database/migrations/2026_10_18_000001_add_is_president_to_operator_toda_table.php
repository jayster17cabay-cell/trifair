<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TODA presidency is a property of a membership, not of the account: a
     * driver may be the president of one TODA while being an ordinary member of
     * another (or the president of several). Each `operator_toda` row therefore
     * carries an `is_president` flag, and a partial unique index guarantees a
     * TODA never has more than one president.
     *
     * Existing presidents are seeded from `users.toda_id` (the pre-refactor
     * source of truth for the operator_president role) so nothing is lost.
     */
    public function up()
    {
        Schema::table('operator_toda', function (Blueprint $table) {
            $table->boolean('is_president')->default(false)->after('status');
        });

        // One president per TODA. Written as raw SQL because partial indexes are
        // not expressible through the schema builder. Postgres (production) and
        // SQLite (tests) both support them; other drivers rely on the
        // application-level guard only.
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX operator_toda_one_president ON operator_toda (toda_id) WHERE is_president = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX operator_toda_one_president ON operator_toda (toda_id) WHERE is_president = 1');
        }

        $this->backfill();
    }

    /**
     * Promote every existing operator_president account: make sure their
     * membership row for their TODA exists (and is active) and flag it as the
     * presidency. Only the first president per TODA is taken, so the unique
     * index can never be violated even if legacy data was inconsistent.
     */
    private function backfill(): void
    {
        $now = now();
        $claimed = [];

        $presidents = DB::table('users')
            ->where('role', 'operator_president')
            ->whereNotNull('toda_id')
            ->select('id', 'toda_id')
            ->get();

        foreach ($presidents as $user) {
            $todaId = (int) $user->toda_id;
            if (isset($claimed[$todaId])) {
                continue;
            }

            $operatorId = DB::table('operators')->where('user_id', $user->id)->value('id');
            if (!$operatorId) {
                continue; // No operator row: nothing to flag; code falls back to users.toda_id.
            }

            $row = DB::table('operator_toda')
                ->where('operator_id', $operatorId)
                ->where('toda_id', $todaId)
                ->first();

            if ($row) {
                DB::table('operator_toda')->where('id', $row->id)->update([
                    'is_president' => true,
                    'status' => 'active',
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('operator_toda')->insert([
                    'operator_id' => $operatorId,
                    'toda_id' => $todaId,
                    'status' => 'active',
                    'is_president' => true,
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $claimed[$todaId] = true;
        }
    }

    public function down()
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS operator_toda_one_president');
        }

        Schema::table('operator_toda', function (Blueprint $table) {
            $table->dropColumn('is_president');
        });
    }
};
