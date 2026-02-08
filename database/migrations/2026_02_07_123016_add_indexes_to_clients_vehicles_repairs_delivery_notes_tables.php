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
        Schema::table('clients', function (Blueprint $table) {
            $table->index('name');
            $table->index('registered_at');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('added_at');
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->index('started_at');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->index('added_at');
            $table->index('supplier');
            $table->index('family');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['registered_at']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['added_at']);
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropIndex(['added_at']);
            $table->dropIndex(['supplier']);
            $table->dropIndex(['family']);
        });
    }
};
