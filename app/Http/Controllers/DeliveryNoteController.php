<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class DeliveryNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryNotes = DeliveryNote::all();
        return Inertia::render('DeliveryNotes/Index', [
            'delivery_notes' => $deliveryNotes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:generic,corrective',
            'supplier' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unitary_price' => 'required|numeric|min:0',
            'RRP' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'margin' => 'required|numeric',
            'profit' => 'required|numeric',
            'added_at' => 'required|date',
        ]);

        $deliveryNote = new DeliveryNote();
        $deliveryNote->type = $request->type;
        $deliveryNote->supplier = $request->supplier;
        $deliveryNote->family = $request->family;
        $deliveryNote->quantity = $request->quantity;
        $deliveryNote->unitary_price = $request->unitary_price;
        $deliveryNote->RRP = $request->RRP;
        $deliveryNote->cost = $request->cost;
        $deliveryNote->margin = $request->margin;
        $deliveryNote->profit = $request->profit;
        $deliveryNote->added_at = $request->added_at;
        $deliveryNote->save();

        return Redirect::route('delivery_notes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
