<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->index(['viewed_at', 'path'], 'page_views_viewed_at_path_index');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->index(['clicked_at', 'path'], 'clicks_clicked_at_path_index');
        });

        Schema::table('educations', function (Blueprint $table) {
            $table->index('sort_order', 'educations_sort_order_index');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->index('category', 'skills_category_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('page_views_viewed_at_path_index');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex('clicks_clicked_at_path_index');
        });

        Schema::table('educations', function (Blueprint $table) {
            $table->dropIndex('educations_sort_order_index');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex('skills_category_index');
        });
    }
};
