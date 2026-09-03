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
            $table->string('ip', 64)->nullable()->change();
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->string('ip', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->change();
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->change();
        });
    }
};
