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
                    \Log::error('Error al eliminar la clave foránea: ' . $e->getMessage());
                }
                
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('step_id');
                });
                
                \Log::info('Columna step_id eliminada correctamente');
            } else {
                \Log::info('La columna step_id no existe en la tabla repairs');
            }
            
            if (Schema::hasColumn('repairs', 'status')) {
                Schema::table('repairs', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
                
                \Log::info('Columna status eliminada correctamente');
            } else {
                \Log::info('La columna status no existe en la tabla repairs');
            }
        } catch (\Exception $e) {
            \Log::error('Error en fix_repairs_table_structure: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
}; 