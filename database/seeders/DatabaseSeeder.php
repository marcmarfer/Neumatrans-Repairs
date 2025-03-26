<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Repair;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
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

        $repairTypes = [];
        for ($i = 0; $i < 12; $i++) {
            $repairTypes[] = RepairType::create([
                'name' => fake()->unique()->words(rand(1, 3), true),
                'description' => fake()->sentence(10)
            ]);
        }

        $possibleSteps = ['Recepción', 'Diagnosis', 'Recambios', 'En reparación', 
                      'Pruebas', 'Finalizado', 'Verificar', 'Validar', 'Limpieza',
                      'Montaje', 'Pintura', 'Test', 'Revisión'];
        
        foreach ($repairTypes as $repairType) {
            $stepsCount = rand(3, 6);
            $stepsToUse = fake()->randomElements($possibleSteps, $stepsCount);
            
            if (!in_array('Finalizado', $stepsToUse)) {
                $stepsToUse[] = 'Finalizado';
            }
            
            foreach ($stepsToUse as $index => $step) {
                RepairTypeStep::create([
                    'repair_type_id' => $repairType->id,
                    'step_name' => $step,
                    'step_order' => $index + 1
                ]);
            }
        }

        for ($i = 0; $i < 20; $i++) {
            $repairType = $repairTypes[array_rand($repairTypes)];
            $vehicle = Vehicle::inRandomOrder()->first();
            
            $steps = RepairTypeStep::where('repair_type_id', $repairType->id)->get();
            $randomStep = $steps->random();
            
            Repair::create([
                'repair_type_id' => $repairType->id,
                'vehicle_id' => $vehicle->id,
                'observations' => fake()->paragraph(),
                'step' => $randomStep->id,
                'started_at' => Carbon::now()->subDays(rand(0, 30))->toDateString()
            ]);
        }

        DeliveryNote::factory(10)->create([
            'added_at' => Carbon::now()->toDateString(),
        ]);
    }
}
