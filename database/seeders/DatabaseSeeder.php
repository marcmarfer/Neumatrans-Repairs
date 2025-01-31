<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Repair;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
use App\Models\RepairStepStatus;
use App\Models\Client;
use App\Models\DeliveryNote;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(5)->create();

        Client::factory(20)->create();

        Vehicle::factory(15)->create();

        $repairTypes = RepairType::factory(5)->create();

        $repairTypes->each(function ($repairType) {
            RepairTypeStep::factory(3)->create([
                'repair_type_id' => $repairType->id,
            ]);
        });

        $repairs = Repair::factory(20)->create([
            'started_at' => Carbon::now()->toDateString(),
        ]);

        $repairs->each(function ($repair) {
            $repairTypeSteps = RepairTypeStep::where('repair_type_id', $repair->repair_type_id)->get();

            foreach ($repairTypeSteps as $step) {
                RepairStepStatus::factory()->create([
                    'repair_id' => $repair->id,
                    'repair_type_step_id' => $step->id,
                ]);
            }
        });

        DeliveryNote::factory(10)->create([
            'added_at' => Carbon::now()->toDateString(),
            'RRP' => 100.00,
            'cost' => 60.00,
            'margin' => 40.00,
            'profit' => 40.00,
        ]);
    }
}
