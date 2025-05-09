<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RepairTrackingController extends Controller
{
    public function index($token)
    {
        $repair = Repair::where('tracking_token', $token)
            ->with(['vehicle.client', 'repairType', 'repairOrder.repairs.repairType'])
            ->firstOrFail();

        return Inertia::render('Repairs/Track', [
            'repair' => $repair,
            'vehicle' => $repair->vehicle,
            'client' => $repair->vehicle->client,
            'repairType' => $repair->repairType,
            'repairOrder' => $repair->repairOrder,
            'statusLabels' => [
                'reception' => 'En recepción',
                'diagnosing' => 'Diagnóstico',
                'in_repair' => 'En reparación',
                'finished' => 'Finalizado'
            ]
        ]);
    }
}
