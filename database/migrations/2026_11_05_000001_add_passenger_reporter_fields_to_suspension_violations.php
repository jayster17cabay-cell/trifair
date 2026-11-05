<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passenger-filed violation reports: a passenger scanning the QR of a
 * suspended / inactive operator can report them instead of rating. They may
 * not be signed in, so reported_by becomes nullable and the reporter's name
 * and contact are captured for follow-up. Signed-in passengers are still
 * linked through reported_by as before.
 *
 * SQLite cannot drop foreign keys, so on sqlite (the test database) the
 * column is simply made nullable and the original foreign key is left in
 * place; on Postgres (production) the key is swapped to nullOnDelete.
 */
class AddPassengerReporterFieldsToSuspensionViolations extends Migration
{
    public function up()
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('suspension_violations', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign(['reported_by']);
            }
            $table->unsignedBigInteger('reported_by')->nullable()->change();
            if (!$isSqlite) {
                $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
            }
            $table->string('reporter_name', 100)->nullable()->after('reported_by');
            $table->string('reporter_contact', 100)->nullable()->after('reporter_name');
        });
    }

    public function down()
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('suspension_violations', function (Blueprint $table) use ($isSqlite) {
            $table->dropColumn(['reporter_name', 'reporter_contact']);
            if (!$isSqlite) {
                $table->dropForeign(['reported_by']);
                // Reported_by is deliberately left nullable: rows filed by guests
                // would violate the old NOT NULL constraint, and dropping them
                // would destroy evidence. Only the foreign key is restored.
                $table->foreign('reported_by')->references('id')->on('users');
            }
        });
    }
}
