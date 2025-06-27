<?php

namespace App\Http\Controllers;

use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VehicleModelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255|unique:vehicle_models,name,NULL,id,brand_id,' . $request->brand_id,
        ]);

        VehicleModel::create($validated);

        return Redirect::back()->with('success', 'Modelo creado correctamente.');
    }
} 