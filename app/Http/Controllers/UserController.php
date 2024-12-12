<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getUserLoggedIn()
    {
        $userLogged = Auth::user();
        return response()->json($userLogged);
    }
}