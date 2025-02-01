<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Repair;

class RepairController extends Controller
{
    public function index()
    {
        return Inertia::render('Repairs', [
            'repairs' => Repair::all()
        ]);
    }
}