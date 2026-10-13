<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resolution details for a reviewed complaint:
     *   - for rejected complaints this is the reason the complaint was not
     *     validated (shown to the passenger);
     *   - for solved complaints this is how the matter was resolved.
     *
     * Nullable so existing rows and the plain accept action are unaffected.
     */
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->text('resolution_note')->nullable()->after('solved_at');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('resolution_note');
        });
    }
};
