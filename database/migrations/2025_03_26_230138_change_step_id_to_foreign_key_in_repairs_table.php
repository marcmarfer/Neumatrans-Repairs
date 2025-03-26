<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn('step_id');
        });
        
        Schema::table('repairs', function (Blueprint $table) {
            $table->foreignId('step_id')->after('observations')->constrained('repair_type_steps')->onDelete('cascade');
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['step_id']);
            $table->dropColumn('step_id');
        });
        
        Schema::table('repairs', function (Blueprint $table) {
            $table->enum('step_id', ['pending', 'in_progress', 'completed'])->default('pending')->after('observations');
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
