<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScaleIndexesToRatingsTable extends Migration
{
    /**
     * Composite indexes tuned for the 10k-operator target:
     * the dashboard aggregates and reports join/group by these columns.
     */
    public function up()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->index(['is_valid', 'operator_id'], 'ratings_valid_operator_idx');
            $table->index(['operator_id', 'created_at'], 'ratings_operator_created_idx');
            $table->index(['is_valid', 'is_reviewed', 'created_at'], 'ratings_valid_review_created_idx');
            $table->index(['is_valid', 'rating', 'complaint_type'], 'ratings_valid_rating_complaint_idx');
        });
    }

    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex('ratings_valid_operator_idx');
            $table->dropIndex('ratings_operator_created_idx');
            $table->dropIndex('ratings_valid_review_created_idx');
            $table->dropIndex('ratings_valid_rating_complaint_idx');
        });
    }
}
