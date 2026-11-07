<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panel follow-up checklist captured while resolving or escalating a
 * complaint. Stored as a JSON array of checked follow-up question keys so the
 * TFRB/superadmin re-review can see which professional-panel follow-ups were
 * asked (or deemed warranted) before escalating / re-solving.
 */
class AddEscalationChecklistToRatingsTable extends Migration
{
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->text('escalation_checklist')->nullable();
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('escalation_checklist');
        });
    }
}
