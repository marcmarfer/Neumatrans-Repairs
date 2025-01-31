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
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['generic', 'corrective']);
            $table->string('supplier');
            $table->string('family');
            $table->decimal('RRP', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('margin', 10, 2);
            $table->decimal('profit', 10, 2);
            $table->date('added_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
