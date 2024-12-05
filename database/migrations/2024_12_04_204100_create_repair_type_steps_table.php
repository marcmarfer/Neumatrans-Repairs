<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_type_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_type_id')->constrained('repair_types')->onDelete('cascade');
            $table->string('step_name');
            $table->integer('step_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_type_steps');
    }
};
