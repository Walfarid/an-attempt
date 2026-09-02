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
        Schema::table('guide_post', function (Blueprint $table) {
            $table->index('post_id', 'guide_post_post_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guide_post', function (Blueprint $table) {
            $table->dropIndex('guide_post_post_id_index');
        });
    }
};
