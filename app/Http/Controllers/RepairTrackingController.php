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
            ->with(['vehicle.client', 'repairType', 'currentStep'])
            ->firstOrFail();

        return Inertia::render('Repairs/Track', [
            'repair' => $repair,
            'vehicle' => $repair->vehicle,
            'client' => $repair->vehicle->client,
            'repairType' => $repair->repairType,
            'currentStep' => $repair->currentStep,
        ]);
    }
}
