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
        try {
            if (Schema::hasColumn('repairs', 'step_id')) {
                try {
                    Schema::table('repairs', function (Blueprint $table) {
                        $table->dropForeign(['step_id']);
                    });
                } catch (\Exception $e) {
                    \Log::error('Error al eliminar la clave foránea step_id: ' . $e->getMessage());
                }
                
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step_id');
                });
            }
            
            if (!Schema::hasColumn('repairs', 'step_id')) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->bigInteger('step_id')->unsigned()->after('repair_type_id');
                });
            }
        } catch (\Exception $e) {
            \Log::error('Error en la migración change_step_id_type: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasColumn('repairs', 'step_id')) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step_id');
                });
            }
            
            if (!Schema::hasColumn('repairs', 'step_id')) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->enum('step_id', ['pending', 'in_progress', 'completed'])->default('pending')->after('repair_type_id');
                });
            }
        } catch (\Exception $e) {
            \Log::error('Error al revertir la migración change_step_id_type: ' . $e->getMessage());
        }
    }
}; 