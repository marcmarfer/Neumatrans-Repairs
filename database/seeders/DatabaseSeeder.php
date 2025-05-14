<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Repair;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\RepairOrder;
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

        $clients = Client::all();
        $user = User::first() ?? User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $client = $clients->random();
            
            $repairOrder = RepairOrder::create([
                'client_id' => $client->id,
                'observations' => fake()->paragraph(),
                'status' => fake()->randomElement(['reception', 'in_repair', 'finished']),
                'created_by' => $user->id
            ]);
            
            if ($repairOrder->status === 'finished') {
                $repairOrder->completed_at = Carbon::now();
                $repairOrder->save();
            }
            
            $repairCount = rand(1, 3);
            for ($j = 0; $j < $repairCount; $j++) {
                $repairType = $repairTypes[array_rand($repairTypes)];
                $vehicle = Vehicle::where('client_id', $client->id)->inRandomOrder()->first() 
                    ?? Vehicle::factory()->create(['client_id' => $client->id]);
                
                $repair = Repair::create([
                    'repair_type_id' => $repairType->id,
                    'vehicle_id' => $vehicle->id,
                    'repair_order_id' => $repairOrder->id,
                    'observations' => fake()->paragraph(),
                    'started_at' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                    'tracking_token' => \Illuminate\Support\Str::uuid()
                ]);
                
                if ($repairOrder->status === 'finished') {
                    $repair->completed_at = Carbon::now();
                    $repair->save();
                }
            }
        }

        DeliveryNote::factory(10)->create([
            'added_at' => Carbon::now()->toDateString(),
        ]);
    }
}
