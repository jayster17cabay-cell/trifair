<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scope each rating to the franchise term it belongs to. This is what makes
     * "current term" statistics start at zero after a renewal while the
     * "All Time" view can still aggregate every term.
     *
     * Existing operators are backfilled with a single active term so their
     * current view keeps showing exactly what it showed before (no visible
     * change) and every existing rating is attached to it.
     */
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('franchise_term_id')->nullable()
                ->constrained('franchise_terms')->nullOnDelete();
        });

        $this->backfill();
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('franchise_term_id');
        });
    }

    private function backfill(): void
    {
        $now = now();

        DB::table('operators')->orderBy('id')->chunk(100, function ($operators) use ($now) {
            $rows = [];
            foreach ($operators as $operator) {
                $rows[] = [
                    'operator_id' => $operator->id,
                    'franchise_no' => 'FR-' . str_pad((string) $operator->id, 4, '0', STR_PAD_LEFT),
                    'issued_at' => null,
                    'valid_until' => null,
                    'status' => 'active',
                    'renewed_from_id' => null,
                    'notes' => 'Initial term (backfilled)',
                    'processed_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($rows)) {
                DB::table('franchise_terms')->insert($rows);
            }
        });

        DB::table('franchise_terms')->select('id', 'operator_id')->orderBy('id')->chunk(100, function ($terms) {
            foreach ($terms as $term) {
                DB::table('ratings')
                    ->where('operator_id', $term->operator_id)
                    ->whereNull('franchise_term_id')
                    ->update(['franchise_term_id' => $term->id]);
            }
        });
    }
};
