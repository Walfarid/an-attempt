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
        Schema::table('post_tag', function (Blueprint $table) {
            // Reverse pivot lookups (tag pages: WHERE tag_id = ?) cannot use
            // the composite PK's leading column; the single-column index is
            // required by the pivot-index rule.
            $table->index('tag_id', 'post_tag_tag_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_tag', function (Blueprint $table) {
            $table->dropIndex('post_tag_tag_id_index');
        });
    }
};
