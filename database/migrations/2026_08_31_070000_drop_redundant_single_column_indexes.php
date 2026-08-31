<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop single-column indexes that are fully covered by composite indexes
     * with the same leading column, leaving composite-only plans unchanged:
     *
     * - page_views.viewed_at          → covered by (viewed_at, path)
     * - project_screenshots.project_id → covered by (project_id, sort_order)
     * - project_screenshots.sort_order → no query filters/orders by it alone
     *   (the Round 13 composite serves every screenshots pattern).
     *
     * Coverage proven via EXPLAIN on MariaDB scratch tables (identical plans,
     * composites selected in every pattern); write-path micro-benchmarks show
     * the redundant indexes only add maintenance/storage with zero read gain.
     */
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('page_views_viewed_at_index');
        });

        Schema::table('project_screenshots', function (Blueprint $table) {
            $table->dropIndex('project_screenshots_project_id_index');
            $table->dropIndex('project_screenshots_sort_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->index('viewed_at', 'page_views_viewed_at_index');
        });

        Schema::table('project_screenshots', function (Blueprint $table) {
            $table->index('project_id', 'project_screenshots_project_id_index');
            $table->index('sort_order', 'project_screenshots_sort_order_index');
        });
    }
};
