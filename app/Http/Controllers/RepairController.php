<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RepairController extends Controller
{
    public function index()
    {
        $repair_types = RepairType::with(['repairTypeStep' => function($query) {
            $query->orderBy('step_order');
        }])->get();
        
        return Inertia::render('Repairs/Index', [
            'repairs' => Repair::with(['repairType', 'vehicle.client', 'currentStep'])->get(),
            'vehicles' => Vehicle::with('client')->get(),
            'repair_types' => $repair_types
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'observations' => 'nullable|string',
            'step' => 'required|exists:repair_type_steps,id',
            'started_at' => 'required|date',
        ]);

        $repair = new Repair();
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->step = $request->step;
        $repair->started_at = $request->started_at;
        $repair->save();

        return Redirect::route('repairs.index');
    }
}