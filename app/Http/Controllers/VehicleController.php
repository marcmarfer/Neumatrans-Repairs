<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Brand;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VehicleController extends Controller
{
    public function index()
    {
        return Inertia::render('Vehicles/Index', [
            'vehicles' => Vehicle::with(['client:id,name,DNI', 'brand:id,name', 'model:id,name,brand_id'])
                ->orderBy('added_at', 'desc')
                ->orderBy('id', 'desc')
                ->get(),
            'clients' => Client::select('id', 'name', 'DNI')->orderBy('name')->get(),
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
            'models' => VehicleModel::select('id', 'name', 'brand_id')->orderBy('name')->get(),
        ]);
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