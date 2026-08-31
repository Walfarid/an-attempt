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
        Schema::table('project_screenshots', function (Blueprint $table) {
            $table->index(['project_id', 'sort_order'], 'project_screenshots_project_id_sort_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_screenshots', function (Blueprint $table) {
            $table->dropIndex('project_screenshots_project_id_sort_order_index');
        });
    }
};
