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
        Schema::create('repair_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('status', ['reception', 'diagnosing', 'in_repair', 'finished'])->default('reception');
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->foreignId('repair_order_id')->nullable()->after('id')->constrained('repair_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['repair_order_id']);
            $table->dropColumn('repair_order_id');
        });

        Schema::dropIfExists('repair_orders');
    }
};
