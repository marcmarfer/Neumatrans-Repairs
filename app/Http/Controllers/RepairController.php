<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
use App\Mail\RepairStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

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
            'step_id' => 'required|exists:repair_type_steps,id',
            'started_at' => 'required|date',
        ]);

        $repair = new Repair();
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->step_id = $request->step_id;
        $repair->started_at = $request->started_at;
        $repair->tracking_token = Str::uuid();
        $repair->save();

        $repair->load(['vehicle.client', 'repairType', 'currentStep']);

        if ($repair->vehicle->client->email) {
            try {
                Mail::to($repair->vehicle->client->email)
                    ->send(new RepairStatusNotification($repair));
            } catch (\Exception $e) {
                \Log::error('Failed to send email notification: ' . $e->getMessage());
            }
        }

        return Redirect::route('repairs.index');
    }

    public function update(Request $request, Repair $repair)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'observations' => 'nullable|string',
            'step_id' => 'required|exists:repair_type_steps,id',
            'started_at' => 'required|date',
        ]);

        $oldStepId = $repair->step_id;
        $newStepId = $request->step_id;
        
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->step_id = $newStepId;
        $repair->started_at = $request->started_at;
        
        if (!$repair->tracking_token) {
            $repair->tracking_token = Str::uuid();
        }
        
        $repairType = RepairType::with(['repairTypeStep' => function($query) {
            $query->orderBy('step_order', 'desc');
        }])->find($repair->repair_type_id);
        
        $isLastStep = false;
        if ($repairType && count($repairType->repairTypeStep) > 0) {
            $lastStep = $repairType->repairTypeStep[0];
            $isLastStep = $lastStep->id == $newStepId && $oldStepId != $newStepId;
        }
        
        if ($isLastStep && !$repair->completed_at) {
            $repair->completed_at = now();
        }
        
        $repair->save();
        
        $repair->load(['vehicle.client', 'repairType', 'currentStep']);
        
        if ($isLastStep && $repair->completed_at && $repair->vehicle->client->email) {
            try {
                Mail::to($repair->vehicle->client->email)
                    ->send(new RepairStatusNotification($repair, true));
            } catch (\Exception $e) {
                \Log::error('Failed to send completion email: ' . $e->getMessage());
            }
        }

        return Redirect::route('repairs.index');
    }

    public function destroy(Repair $repair)
    {
        try {
            $repair->delete();
            return Redirect::route('repairs.index');
        } catch (\Exception $e) {
            return Redirect::route('repairs.index')->with('error', 'No se pudo eliminar la reparación.');
        }
    }
}