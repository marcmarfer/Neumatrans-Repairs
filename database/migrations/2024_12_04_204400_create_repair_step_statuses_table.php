<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_step_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs')->onDelete('cascade');
            $table->foreignId('repair_type_step_id')->constrained('repair_type_steps')->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_step_statuses');
    }
};
