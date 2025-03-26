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
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'step_id')) {
                $table->dropForeign(['step_id']);
                $table->dropColumn('step_id');
            }
            
            $table->bigInteger('step_id')->unsigned()->after('repair_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'step_id')) {
                $table->dropColumn('step_id');
            }
            
            $table->enum('step_id', ['pending', 'in_progress', 'completed'])->default('pending')->after('repair_type_id');
        });
    }
};
