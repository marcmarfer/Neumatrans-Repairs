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
            $hasStepId = Schema::hasColumn('repairs', 'step_id');
            $hasStep = Schema::hasColumn('repairs', 'step');
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            if ($hasStepId) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step_id');
                });
            } elseif ($hasStep) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step');
                });
            }
            
            if (!Schema::hasColumn('repairs', 'step_id')) {
                try {
                    Schema::table('repairs', function (Blueprint $table) {
                        $table->foreignId('step_id')->after('observations')->constrained('repair_type_steps')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    \Log::error('Error al agregar la columna step_id: ' . $e->getMessage());
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            \Log::error('Error en la migración change_step_id_to_foreign_key: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            if (Schema::hasColumn('repairs', 'step_id')) {
                try {
                    Schema::table('repairs', function (Blueprint $table) {
                        $table->dropForeign(['step_id']);
                    });
                } catch (\Exception $e) {
                    \Log::error('Error al eliminar la clave foránea: ' . $e->getMessage());
                }
                
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step_id');
                });
            }
            
            if (!Schema::hasColumn('repairs', 'step_id')) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->enum('step_id', ['pending', 'in_progress', 'completed'])->default('pending')->after('observations');
                });
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            \Log::error('Error al revertir la migración change_step_id_to_foreign_key: ' . $e->getMessage());
        }
    }
}; 