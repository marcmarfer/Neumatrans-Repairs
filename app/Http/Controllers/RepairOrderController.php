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
use App\Traits\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RepairOrderController extends Controller
{
    use ExportsCsv;

    /**
     * Build the base filtered query (search + date range) shared by index() and exportCsv().
     * Does NOT apply tab or eager loading — callers add those.
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = RepairOrder::query()
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($qb) use ($search) {
                $qb->whereHas('client', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })->orWhereHas('repairs.vehicle', function ($q) use ($search) {
                    $q->where('plate_number', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($request->filled('start')) {
            $query->whereDate('created_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('created_at', '<=', $request->input('end'));
        }

        return $query;
    }

    /**
     * Apply tab filter (in_progress / completed) to the query.
     */
    private function applyTabFilter($query, Request $request)
    {
        $tab = $request->input('tab', 'in_progress');
        if ($tab === 'completed') {
            $query->whereNotNull('completed_at');
        } else {
            $query->whereNull('completed_at');
        }
        return $query;
    }

    public function index(Request $request)
    {
        $baseQuery = $this->buildFilteredQuery($request)
            ->with(['client', 'repairs.vehicle.brand', 'repairs.vehicle.model', 'repairs.repairType', 'createdBy']);

        // Tab counts
        $inProgressCount = (clone $baseQuery)->whereNull('completed_at')->count();
        $completedCount = (clone $baseQuery)->whereNotNull('completed_at')->count();

        // Apply tab filter
        $this->applyTabFilter($baseQuery, $request);

        return Inertia::render('RepairOrders/Index', [
            'repair_orders' => $baseQuery->paginate(10)->withQueryString(),
            'in_progress_count' => $inProgressCount,
            'completed_count' => $completedCount,
            'clients' => Client::all(),
            'vehicles' => Vehicle::with(['client', 'brand', 'model'])->get(),
            'repair_types' => RepairType::all(),
            'filters' => $request->only(['q', 'start', 'end', 'tab']),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['client', 'repairs.vehicle.brand', 'repairs.vehicle.model', 'repairs.repairType']);

        $this->applyTabFilter($query, $request);

        $records = $query->get();

        $statusMap = [
            'reception' => 'En recepción',
            'diagnosing' => 'Diagnóstico',
            'in_repair' => 'En reparación',
            'finished' => 'Finalizado',
        ];

        return $this->streamCsv(
            $records,
            ['ID', 'Cliente', 'Vehículos', 'Estado', 'Observaciones', 'Fecha de Creación', 'Fecha de Finalización'],
            function ($record) use ($statusMap) {
                $vehicles = '';
                if ($record->repairs && $record->repairs->count() > 0) {
                    $uniqueVehicles = [];
                    foreach ($record->repairs as $repair) {
                        if ($repair->vehicle) {
                            $plate = $repair->vehicle->plate_number;
                            if (!isset($uniqueVehicles[$plate])) {
                                $brandName = $repair->vehicle->brand->name ?? '';
                                $modelName = $repair->vehicle->model->name ?? '';
                                $name = trim("{$brandName} {$modelName}");
                                $uniqueVehicles[$plate] = $name ? "{$name} ({$plate})" : $plate;
                            }
                        }
                    }
                    $vehicles = implode(', ', $uniqueVehicles);
                }

                return [
                    $record->id,
                    $record->client->name ?? '',
                    $vehicles,
                    $statusMap[$record->status] ?? $record->status,
                    $record->observations,
                    $record->created_at ? $record->created_at->format('Y-m-d') : '',
                    $record->completed_at ? $record->completed_at->format('Y-m-d') : '',
                ];
            },
            'ordenes_reparacion.csv'
        );
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