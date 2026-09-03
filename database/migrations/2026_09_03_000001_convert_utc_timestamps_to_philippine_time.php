<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConvertUtcTimestampsToPhilippineTime extends Migration
{
    /**
     * One-time data correction: every timestamp in the database was stored in
     * UTC wall-clock time while the application is used by operators and
     * passengers in the Philippines (UTC+8). Because ratings and complaints
     * timestamps are used as official evidence, they must reflect Philippine
     * local time. This shifts all stored (UTC) wall-clock values forward 8
     * hours so they display correctly after the application timezone was set
     * to Asia/Manila.
     *
     * @var array<string, string[]>
     */
    private array $tables = [
        'ratings' => ['created_at', 'updated_at'],
        'operator_responses' => ['created_at', 'updated_at'],
        'rating_proofs' => ['created_at', 'updated_at'],
        'activity_logs' => ['created_at', 'updated_at'],
        'notifications' => ['created_at', 'updated_at'],
        'users' => ['created_at', 'updated_at'],
        'operators' => ['created_at', 'updated_at'],
        'todas' => ['created_at', 'updated_at'],
    ];

    public function up()
    {
        foreach ($this->tables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = array_values(array_filter($columns, fn ($col) => Schema::hasColumn($table, $col)));

            if (! $existing || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $rows = DB::table($table)->select(array_merge(['id'], $existing))->get();

            foreach ($rows as $row) {
                $data = [];

                foreach ($existing as $col) {
                    $value = $row->{$col};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    // Treat the stored value as a plain UTC wall-clock string and
                    // advance it 8 hours to Philippine local time.
                    $data[$col] = Carbon::parse($value)->addHours(8)->format('Y-m-d H:i:s');
                }

                if ($data) {
                    DB::table($table)->where('id', $row->id)->update($data);
                }
            }
        }
    }

    public function down()
    {
        // Non-destructive; going back is not expected for evidence records.
    }
}
