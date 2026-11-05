<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Operator names are displayed last-name first ("Dela Cruz Juan Santos").
     * Backfill the stored display name for existing operator accounts and
     * populate the split columns for legacy rows that only have `name`.
     */
    public function up()
    {
        DB::table('users')
            ->whereIn('role', ['operator', 'operator_president'])
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $first = trim((string) $user->first_name);
                    $middle = trim((string) $user->middle_name);
                    $last = trim((string) $user->last_name);

                    if ($first === '' && $last === '') {
                        [$first, $middle, $last] = $this->splitLegacyName($user->name);
                    }

                    $composed = $this->composeName($first, $middle, $last);

                    if ($composed === '') {
                        continue;
                    }

                    $update = [
                        'first_name' => $first !== '' ? $first : null,
                        'middle_name' => $middle !== '' ? $middle : null,
                        'last_name' => $last !== '' ? $last : null,
                    ];

                    if ($composed !== trim((string) $user->name)) {
                        $update['name'] = $composed;
                    }

                    DB::table('users')->where('id', $user->id)->update($update);
                }
            });
    }

    /**
     * Restore the old First Middle Last display order for operator accounts.
     */
    public function down()
    {
        DB::table('users')
            ->whereIn('role', ['operator', 'operator_president'])
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $first = trim((string) $user->first_name);
                    $middle = trim((string) $user->middle_name);
                    $last = trim((string) $user->last_name);

                    if ($first === '' && $last === '') {
                        continue;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'name' => trim(preg_replace('/\s+/', ' ', "{$first} {$middle} {$last}")),
                    ]);
                }
            });
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitLegacyName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) <= 1) {
            return [$parts[0] ?? '', '', ''];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return [$first, implode(' ', $parts), $last];
    }

    private function composeName(string $first, string $middle, string $last): string
    {
        return trim(preg_replace('/\s+/', ' ', "{$last} {$first} {$middle}"));
    }
};
