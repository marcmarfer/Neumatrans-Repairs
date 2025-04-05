<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Vehicle;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VehicleController extends Controller
{
    public function index()
    {
        return Inertia::render('Vehicles/Index', [
            'vehicles' => Vehicle::with('client')->get(),
            'clients' => Client::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'VIN' => 'nullable|string|unique:vehicles,VIN',
            'motor_type' => 'nullable|string|max:100',
            'added_at' => 'required|date',
        ]);

        $vehicle = new Vehicle();
        $vehicle->client_id = $request->client_id;
        $vehicle->plate_number = $request->plate_number;
        $vehicle->brand = $request->brand;
        $vehicle->model = $request->model;
        $vehicle->VIN = $request->VIN;
        $vehicle->motor_type = $request->motor_type;
        $vehicle->added_at = $request->added_at;
        $vehicle->save();

        return Redirect::route('vehicles.index');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'plate_number' => 'required|string|unique:vehicles,plate_number,' . $vehicle->id,
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'VIN' => 'nullable|string|unique:vehicles,VIN,' . $vehicle->id,
            'motor_type' => 'nullable|string|max:100',
            'added_at' => 'required|date',
        ]);

        $vehicle->client_id = $request->client_id;
        $vehicle->plate_number = $request->plate_number;
        $vehicle->brand = $request->brand;
        $vehicle->model = $request->model;
        $vehicle->VIN = $request->VIN;
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