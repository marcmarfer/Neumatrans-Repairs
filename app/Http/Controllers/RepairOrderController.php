<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\RepairOrder;
use App\Models\Repair;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairTypeStep;
use App\Mail\RepairStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RepairOrderController extends Controller
{
    public function index()
    {
        return Inertia::render('RepairOrders/Index', [
            'repair_orders' => RepairOrder::with(['client', 'repairs.vehicle.brand', 'repairs.vehicle.model', 'repairs.repairType', 'createdBy'])->get(),
            'clients' => Client::all(),
            'vehicles' => Vehicle::with(['client', 'brand', 'model'])->get(),
            'repair_types' => RepairType::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'observations' => 'nullable|string',
            'send_email' => 'boolean',
            'repairs' => 'required|array|min:1',
            'repairs.*.repair_type_id' => 'required|exists:repair_types,id',
            'repairs.*.observations' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            $repairOrder = new RepairOrder();
            $repairOrder->client_id = $request->client_id;
            $repairOrder->observations = $request->observations;
            $repairOrder->status = 'reception';
            $repairOrder->created_by = Auth::id();
            $repairOrder->save();

            $vehicle = Vehicle::find($request->vehicle_id);
            if ($vehicle && $vehicle->client_id == $request->client_id) {
                foreach ($request->repairs as $repairData) {
                    $repair = new Repair();
                    $repair->vehicle_id = $request->vehicle_id;
                    $repair->repair_type_id = $repairData['repair_type_id'];
                    $repair->observations = $repairData['observations'];
                    $repair->started_at = now();
                    $repair->tracking_token = Str::uuid();
                    $repair->repair_order_id = $repairOrder->id;
                    $repair->save();
                    
                    if ($request->send_email && $vehicle->client && $vehicle->client->email && !isset($notificationSent)) {
                        try {
                            Mail::to($vehicle->client->email)
                                ->send(new RepairStatusNotification($repair));
                            $notificationSent = true;
                        } catch (\Exception $e) {
                            \Log::error('Failed to send email notification: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            DB::commit();
            return Redirect::route('repair-orders.index');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating repair order: ' . $e->getMessage());
            return Redirect::route('repair-orders.index')->with('error', 'Error al crear la orden de reparación: ' . $e->getMessage());
        }
    }

    public function update(Request $request, RepairOrder $repairOrder)
    {
        if ($repairOrder->status === 'finished' && $request->has('repairs')) {
            $existingRepairIds = $repairOrder->repairs->pluck('id')->toArray();
            $requestRepairIds = collect($request->repairs)->whereNotNull('id')->pluck('id')->toArray();
            
            if (count($request->repairs) > count($existingRepairIds)) {
                return Redirect::route('repair-orders.index')->with('error', 'No se pueden añadir reparaciones a una orden completada.');
            }
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'observations' => 'nullable|string',
            'status' => 'required|in:reception,diagnosing,in_repair,finished',
            'repairs' => 'required|array|min:1',
            'repairs.*.repair_type_id' => 'required|exists:repair_types,id',
            'repairs.*.observations' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $repairOrder->status;
            $newStatus = $request->status;
            
            $repairOrder->client_id = $request->client_id;
            $repairOrder->observations = $request->observations;
            $repairOrder->status = $newStatus;
            
            if ($newStatus === 'finished' && $oldStatus !== 'finished') {
                $repairOrder->completed_at = now();
                
                foreach ($repairOrder->repairs as $repair) {
                    if (!$repair->completed_at) {
                        $repair->completed_at = now();
                        $repair->save();
                    }
                }
                
                if ($request->get('send_completion_email', false) && $repairOrder->client && $repairOrder->client->email) {
                    $firstRepair = $repairOrder->repairs->first();
                    if ($firstRepair) {
                        try {
                            Mail::to($repairOrder->client->email)
                                ->send(new RepairStatusNotification($firstRepair, true));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send completion email: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            $repairOrder->save();
            
            $vehicle = Vehicle::find($request->vehicle_id);
            if ($vehicle && $vehicle->client_id == $request->client_id) {
                $existingRepairs = Repair::where('repair_order_id', $repairOrder->id)
                    ->where('vehicle_id', $request->vehicle_id)
                    ->get()
                    ->keyBy('id');
                
                $processedRepairIds = [];
                
                foreach ($request->repairs as $repairData) {
                    if (isset($repairData['id']) && isset($existingRepairs[$repairData['id']])) {
                        if ($repairOrder->status !== 'finished') {
                            $repair = $existingRepairs[$repairData['id']];
                            $repair->repair_type_id = $repairData['repair_type_id'];
                            $repair->observations = $repairData['observations'];
                            $repair->save();
                        }
                        $processedRepairIds[] = $repairData['id'];
                    } else {
                        if ($repairOrder->status !== 'finished') {
                            $repair = new Repair();
                            $repair->vehicle_id = $request->vehicle_id;
                            $repair->repair_type_id = $repairData['repair_type_id'];
                            $repair->observations = $repairData['observations'];
                            $repair->started_at = now();
                            $repair->tracking_token = Str::uuid();
                            $repair->repair_order_id = $repairOrder->id;
                            $repair->save();
                            
                            $processedRepairIds[] = $repair->id;
                        }
                    }
                }
            }
            
            DB::commit();
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['success' => true]);
            }
            return Redirect::route('repair-orders.index');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating repair order: ' . $e->getMessage());
            return Redirect::route('repair-orders.index')->with('error', 'Error al actualizar la orden de reparación: ' . $e->getMessage());
        }
    }

    public function destroy(RepairOrder $repairOrder)
    {
        try {
            $repairOrder->delete();
            return Redirect::route('repair-orders.index');
        } catch (\Exception $e) {
            return Redirect::route('repair-orders.index')->with('error', 'No se pudo eliminar la orden de reparación.');
        }
    }

    public function resendEmail(Request $request, RepairOrder $repairOrder)
    {
        $request->validate([
            'type' => 'required|in:created,completed',
        ]);
        $firstRepair = $repairOrder->repairs->first();
        if (!$firstRepair) {
            return Redirect::route('repair-orders.index')->with('error', 'No hay reparaciones asociadas.');
        }
        if ($request->type === 'completed') {
            try {
                Mail::to($repairOrder->client->email)
                    ->send(new RepairStatusNotification($firstRepair, true));
            } catch (\Exception $e) {
                \Log::error('Error al reenviar correo de reparación completada: ' . $e->getMessage());
            }
        } else {
            try {
                Mail::to($repairOrder->client->email)
                    ->send(new RepairStatusNotification($firstRepair, false));
            } catch (\Exception $e) {
                \Log::error('Error al reenviar correo de creación de orden: ' . $e->getMessage());
            }
        }
        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return response()->json(['success' => true]);
        }
        return Redirect::route('repair-orders.index');
    }
} 