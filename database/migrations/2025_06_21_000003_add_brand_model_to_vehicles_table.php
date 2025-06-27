<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandModelToVehiclesTable extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'brand')) {
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('vehicles', 'model')) {
                $table->dropColumn('model');
            }

            $table->foreignId('brand_id')->nullable()->after('client_id')->constrained('brands')->onDelete('set null');
            $table->foreignId('model_id')->nullable()->after('brand_id')->constrained('vehicle_models')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
            $table->dropColumn('model_id');
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');

            $table->string('brand')->nullable()->after('client_id');
            $table->string('model')->nullable()->after('brand');
        });
    }
} 