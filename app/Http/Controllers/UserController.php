<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show()
    {
        $userLogged = Auth::user();
        return Inertia::render('Dashboard/Show', [
            'user' => $userLogged
        ]);
    }
}