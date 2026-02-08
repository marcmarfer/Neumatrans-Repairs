<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use App\Models\Supplier;
use App\Models\Family;
use App\Traits\ExportsCsv;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class DeliveryNoteController extends Controller
{
    use ExportsCsv;

    /**
     * Build the base filtered query shared by index() and exportCsv().
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = DeliveryNote::query()
            ->orderBy('added_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($qb) use ($search) {
                $qb->where('supplier', 'LIKE', "%{$search}%")
                   ->orWhere('family', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('start')) {
            $query->whereDate('added_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('added_at', '<=', $request->input('end'));
        }

        return $query;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $totalsRow = (clone $query)->selectRaw('
            COALESCE(SUM(RRP), 0) as total_sold,
            COALESCE(SUM(cost), 0) as total_spent,
            COALESCE(SUM(profit), 0) as total_profit
        ')->first();

        $totalSold = (float) $totalsRow->total_sold;
        $totalSpent = (float) $totalsRow->total_spent;
        $totalProfit = (float) $totalsRow->total_profit;
        $totalMargin = $totalSold > 0 ? ($totalProfit / $totalSold) * 100 : 0;

        // Paginate with only needed columns
        $delivery_notes = $query->select([
            'id', 'type', 'supplier', 'family', 'quantity',
            'unitary_price', 'RRP', 'cost', 'margin', 'profit', 'added_at'
        ])->paginate(10)->withQueryString();

        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();
        $families = Family::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('DeliveryNotes/Index', [
            'delivery_notes' => $delivery_notes,
            'suppliers' => $suppliers,
            'families' => $families,
            'totals' => [
                'totalSold' => $totalSold,
                'totalSpent' => $totalSpent,
                'totalProfit' => $totalProfit,
                'totalMargin' => $totalMargin,
            ],
            'filters' => $request->only(['q', 'start', 'end']),
        ]);
    }

    /**
     * Export filtered delivery notes as CSV.
     */
    public function exportCsv(Request $request)
    {
        $records = $this->buildFilteredQuery($request)
            ->select([
                'id', 'type', 'supplier', 'family', 'quantity',
                'unitary_price', 'RRP', 'cost', 'margin', 'profit', 'added_at'
            ])
            ->get();

        return $this->streamCsv(
            $records,
            ['ID', 'Tipo', 'Proveedor', 'Familia', 'Cantidad', 'Precio Unitario', 'PVP', 'Coste', 'Margen', 'Beneficio', 'Fecha de Alta'],
            fn ($r) => [
                $r->id,
                $r->type === 'corrective' ? 'Correctivo' : ($r->type === 'generic' ? 'Genérico' : $r->type),
                $r->supplier,
                $r->family,
                $r->quantity,
                $r->unitary_price,
                $r->RRP,
                $r->cost,
                $r->margin,
                $r->profit,
                $r->added_at,
            ],
            'albaranes.csv'
        );
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
    public function update(Request $request, DeliveryNote $deliveryNote)
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
     * Remove the specified resource from storage.
     */
    public function destroy(DeliveryNote $deliveryNote)
    {
        try {
            $deliveryNote->delete();
            return Redirect::route('delivery_notes.index');
        } catch (\Exception $e) {
            return Redirect::route('delivery_notes.index')->with('error', 'No se pudo eliminar el albarán.');
        }
    }
}
