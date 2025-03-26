<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\RepairType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RepairController extends Controller
{
    public function index()
    {
        return Inertia::render('Repairs/Index', [
            'repairs' => Repair::with(['repairType', 'vehicle.client'])->get(),
            'vehicles' => Vehicle::with('client')->get(),
            'repair_types' => RepairType::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'observations' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'started_at' => 'required|date',
        ]);

        $repair = new Repair();
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->status = $request->status;
        $repair->started_at = $request->started_at;
        $repair->save();

        return Redirect::route('repairs.index');
    }
}