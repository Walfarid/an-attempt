<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->index('ended_at', 'experiences_ended_at_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('sort_order', 'projects_sort_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropIndex('experiences_ended_at_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_sort_order_index');
        });
    }
};
