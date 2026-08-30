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
        Schema::table('project_skill', function (Blueprint $table) {
            $table->index('skill_id', 'project_skill_skill_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_skill', function (Blueprint $table) {
            $table->dropIndex('project_skill_skill_id_index');
        });
    }
};
