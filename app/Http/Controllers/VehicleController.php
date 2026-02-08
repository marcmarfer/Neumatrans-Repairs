<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Traits\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VehicleController extends Controller
{
    use ExportsCsv;

    /**
     * Build the base filtered query shared by index() and exportCsv().
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Vehicle::query()
            ->orderBy('added_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($qb) use ($search) {
                $qb->where('plate_number', 'LIKE', "%{$search}%")
                   ->orWhereHas('client', function ($q) use ($search) {
                       $q->where('name', 'LIKE', "%{$search}%");
                   });
            });
        }

        if ($request->filled('start')) {
            $query->whereDate('added_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('added_at', '<=', $request->input('end'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['client:id,name,DNI', 'brand:id,name', 'model:id,name,brand_id']);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $query->paginate(10)->withQueryString(),
            'clients' => Client::select('id', 'name', 'DNI')->orderBy('name')->get(),
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
            'models' => VehicleModel::select('id', 'name', 'brand_id')->orderBy('name')->get(),
            'filters' => $request->only(['q', 'start', 'end']),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $records = $this->buildFilteredQuery($request)
            ->with(['client:id,name', 'brand:id,name', 'model:id,name'])
            ->get();

        return $this->streamCsv(
            $records,
            ['ID', 'Cliente', 'Matrícula', 'Marca', 'Modelo', 'VIN', 'Tipo de Motor', 'Fecha de Alta'],
            fn ($r) => [
                $r->id,
                $r->client->name ?? '',
                $r->plate_number,
                $r->brand->name ?? '',
                $r->model->name ?? '',
                $r->VIN,
                $r->motor_type,
                $r->added_at,
            ],
            'vehiculos.csv'
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'plate_number' => [
                'required',
                'string',
                'unique:vehicles,plate_number',
            ],
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'required|exists:vehicle_models,id',
            'VIN' => [
                'nullable',
                'string',
                'unique:vehicles,VIN',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
            ],
            'motor_type' => 'nullable|string|max:100|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.\,\/\(\)]+$/',
            'added_at' => 'required|date|before_or_equal:today',
        ], [
            'plate_number.unique' => 'Esta matrícula ya está registrada.',
            'VIN.regex' => 'El VIN debe tener exactamente 17 caracteres alfanuméricos (sin I, O, Q).',
            'VIN.unique' => 'Este VIN ya está registrado.',
            'motor_type.regex' => 'El tipo de motor contiene caracteres no válidos.',
            'added_at.before_or_equal' => 'La fecha de alta no puede ser futura.',
        ]);

        $vehicle = new Vehicle();
        $vehicle->client_id = $request->client_id;
        $vehicle->plate_number = strtoupper($request->plate_number);
        $vehicle->brand_id = $request->brand_id;
        $vehicle->model_id = $request->model_id;
        $vehicle->VIN = $request->VIN ? strtoupper($request->VIN) : null;
        $vehicle->motor_type = $request->motor_type;
        $vehicle->added_at = $request->added_at;
        $vehicle->save();

        return Redirect::route('vehicles.index');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'plate_number' => [
                'required',
                'string',
                'unique:vehicles,plate_number,' . $vehicle->id,
            ],
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'required|exists:vehicle_models,id',
            'VIN' => [
                'nullable',
                'string',
                'unique:vehicles,VIN,' . $vehicle->id,
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
            ],
            'motor_type' => 'nullable|string|max:100|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.\,\/\(\)]+$/',
            'added_at' => 'required|date|before_or_equal:today',
        ], [
            'plate_number.unique' => 'Esta matrícula ya está registrada.',
            'VIN.regex' => 'El VIN debe tener exactamente 17 caracteres alfanuméricos (sin I, O, Q).',
            'VIN.unique' => 'Este VIN ya está registrado.',
            'motor_type.regex' => 'El tipo de motor contiene caracteres no válidos.',
            'added_at.before_or_equal' => 'La fecha de alta no puede ser futura.',
        ]);

        $vehicle->client_id = $request->client_id;
        $vehicle->plate_number = strtoupper($request->plate_number);
        $vehicle->brand_id = $request->brand_id;
        $vehicle->model_id = $request->model_id;
        $vehicle->VIN = $request->VIN ? strtoupper($request->VIN) : null;
        $vehicle->motor_type = $request->motor_type;
        $vehicle->added_at = $request->added_at;
        $vehicle->save();

        return Redirect::route('vehicles.index');
    }

    public function destroy(Vehicle $vehicle)
    {
        try {
            $vehicle->delete();
            return Redirect::route('vehicles.index');
        } catch (\Exception $e) {
            return Redirect::route('vehicles.index')->with('error', 'No se pudo eliminar el vehículo. Puede tener reparaciones asociadas.');
        }
    }
}