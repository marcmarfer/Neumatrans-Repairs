<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ClientController extends Controller
{
    public function index()
    {

        return Inertia::render('Clients/Index', [
            //gets all clients with the count of vehicles they have
            'clients' => Client::withCount('vehicle')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'DNI' => 'required|string|unique:clients,DNI',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email',
            'telephone' => 'required|string|max:15',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
        ]);

        $client = new Client();
        $client->DNI = $request->DNI;
        $client->name = $request->name;
        $client->email = $request->email;
        $client->telephone = $request->telephone;
        $client->city = $request->city;
        $client->postal_code = $request->postal_code;
        $client->registered_at = now();
        $client->save();

        return Redirect::route('clients.index');
    }
}