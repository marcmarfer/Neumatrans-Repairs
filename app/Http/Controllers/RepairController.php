<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairOrder;
use App\Mail\RepairStatusNotification;
use App\Traits\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class RepairController extends Controller
{
    use ExportsCsv;

    /**
     * Build the base filtered query (search + date range) shared by index() and exportCsv().
     * Does NOT apply tab or eager loading — callers add those.
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Repair::query()
            ->orderBy('started_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($qb) use ($search) {
                $qb->whereHas('vehicle', function ($q) use ($search) {
                    $q->where('plate_number', 'LIKE', "%{$search}%");
                })->orWhereHas('vehicle.client', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($request->filled('start')) {
            $query->whereDate('started_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('started_at', '<=', $request->input('end'));
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
            ->with([
                'repairType:id,name',
                'vehicle:id,plate_number,client_id,brand_id,model_id',
                'vehicle.client:id,name',
                'vehicle.brand:id,name',
                'vehicle.model:id,name',
                'repairOrder:id,client_id,status'
            ]);

        // Tab counts (on the filtered base query, before tab split)
        $inProgressCount = (clone $baseQuery)->whereNull('completed_at')->count();
        $completedCount = (clone $baseQuery)->whereNotNull('completed_at')->count();

        // Apply tab filter
        $this->applyTabFilter($baseQuery, $request);

        $repair_types = RepairType::with('repairTypeStep')->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Repairs/Index', [
            'repairs' => $baseQuery->paginate(10)->withQueryString(),
            'in_progress_count' => $inProgressCount,
            'completed_count' => $completedCount,
            'vehicles' => Vehicle::with(['client:id,name', 'brand:id,name', 'model:id,name,brand_id'])
                ->orderBy('added_at', 'desc')
                ->get(),
            'repair_types' => $repair_types,
            'repair_orders' => RepairOrder::with('client:id,name')->orderBy('id', 'desc')->get(),
            'filters' => $request->only(['q', 'start', 'end', 'tab']),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with([
                'repairType:id,name',
                'vehicle:id,plate_number,client_id,brand_id',
                'vehicle.client:id,name',
                'vehicle.brand:id,name',
            ]);

        $this->applyTabFilter($query, $request);

        $records = $query->get();

        return $this->streamCsv(
            $records,
            ['ID', 'Cliente', 'Marca', 'Matrícula', 'Tipo de Reparación', 'Orden de Reparación', 'Observaciones', 'Fecha de Inicio', 'Fecha de Finalización'],
            fn ($r) => [
                $r->id,
                $r->vehicle->client->name ?? '',
                $r->vehicle->brand->name ?? '',
                $r->vehicle->plate_number ?? '',
                $r->repairType->name ?? '',
                $r->repair_order_id ? "Orden #{$r->repair_order_id}" : 'Sin asignar',
                $r->observations,
                $r->started_at,
                $r->completed_at,
            ],
            'reparaciones.csv'
        );
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
