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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('position');
        });

        Schema::table('board_columns', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('position');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('board_columns', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
