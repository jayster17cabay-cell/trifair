<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A TODA president forwarding a complaint to the TFRB/superadmin must say why
 * they are handing it off. The reason is shown on the forwarded complaint so
 * the receiving team knows what the president already tried or why the case is
 * beyond the TODA's authority.
 */
class AddForwardReasonToRatingsTable extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            if (!Schema::hasColumn('ratings', 'forward_reason')) {
                $table->text('forward_reason')->nullable()->after('forwarded_by');
            }
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            if (Schema::hasColumn('ratings', 'forward_reason')) {
                $table->dropColumn('forward_reason');
            }
        });
    }
}
