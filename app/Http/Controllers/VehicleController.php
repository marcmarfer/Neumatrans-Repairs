<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function index()
    {
        return Inertia::render('Vehicles', [
            'vehicles' => Vehicle::all()
        ]);
    }
}