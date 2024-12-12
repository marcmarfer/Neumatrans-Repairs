<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {

        return Inertia::render('Clients', [
            //gets all clients with the count of vehicles they have
            'clients' => Client::withCount('vehicle')->get()
        ]);
    }
}