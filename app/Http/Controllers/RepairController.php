<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairOrder;
use App\Mail\RepairStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class RepairController extends Controller
{
    public function index()
    {
        $repair_types = RepairType::all();
        
        return Inertia::render('Repairs/Index', [
            'repairs' => Repair::with(['repairType', 'vehicle.client', 'repairOrder'])->get(),
            'vehicles' => Vehicle::with(['client','brand','model'])->get(),
            'repair_types' => $repair_types,
            'repair_orders' => RepairOrder::with('client')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'observations' => 'nullable|string',
            'started_at' => 'required|date',
            'repair_order_id' => 'required|exists:repair_orders,id',
        ]);

        $repairOrder = RepairOrder::find($request->repair_order_id);
        if ($repairOrder && $repairOrder->status === 'finished') {
            return Redirect::route('repairs.index')->with('error', 'No se puede añadir una reparación a una orden completada.');
        }

        $repair = new Repair();
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->started_at = $request->started_at;
        $repair->repair_order_id = $request->repair_order_id;
        $repair->tracking_token = Str::uuid();
        $repair->save();

        return Redirect::route('repairs.index');
    }

    public function update(Request $request, Repair $repair)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_type_id' => 'required|exists:repair_types,id',
            'observations' => 'nullable|string',
            'started_at' => 'required|date',
            'repair_order_id' => 'required|exists:repair_orders,id',
        ]);

        if ($request->repair_order_id != $repair->repair_order_id) {
            $newRepairOrder = RepairOrder::find($request->repair_order_id);
            if ($newRepairOrder && $newRepairOrder->status === 'finished') {
                return Redirect::route('repairs.index')->with('error', 'No se puede asignar una reparación a una orden completada.');
            }
        }
        
        $repair->vehicle_id = $request->vehicle_id;
        $repair->repair_type_id = $request->repair_type_id;
        $repair->observations = $request->observations;
        $repair->started_at = $request->started_at;
        $repair->repair_order_id = $request->repair_order_id;
        
        if (!$repair->tracking_token) {
            $repair->tracking_token = Str::uuid();
        }
        
        $repairOrder = RepairOrder::find($repair->repair_order_id);
        if ($repairOrder && $repairOrder->status === 'finished' && !$repair->completed_at) {
            $repair->completed_at = now();
        }
        
        $repair->save();
        
        $repair->load(['vehicle.client', 'repairType']);
        
        if ($repair->completed_at && $repair->vehicle->client->email && 
            $repair->wasChanged('completed_at')) {
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