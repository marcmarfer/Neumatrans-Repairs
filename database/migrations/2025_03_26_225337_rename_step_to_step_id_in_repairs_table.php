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
        if (Schema::hasColumn('repairs', 'status') && !Schema::hasColumn('repairs', 'step_id')) {
            Schema::table('repairs', function (Blueprint $table) {
                $table->renameColumn('status', 'step_id');
            });
        } else {
            DB::table('migrations')
                ->where('migration', '2025_03_26_225337_rename_step_to_step_id_in_repairs_table')
                ->update(['batch' => DB::table('migrations')->max('batch')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('repairs', 'step_id') && !Schema::hasColumn('repairs', 'status')) {
            Schema::table('repairs', function (Blueprint $table) {
                $table->renameColumn('step_id', 'status');
            });
        }
    }
}; 