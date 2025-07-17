<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RepairOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\RepairStatusNotification;

class RepairOrderController extends Controller
{
    public function index()
    {
        $orders = RepairOrder::with(['client', 'repairs.vehicle.brand', 'repairs.vehicle.model', 'repairs.repairType', 'createdBy'])->get();

        return response()->json($orders);
    }

    public function show(RepairOrder $repairOrder)
    {
        $order = RepairOrder::with(['client', 'repairs.vehicle.brand', 'repairs.vehicle.model', 'repairs.repairType', 'createdBy'])->findOrFail($repairOrder->id);

        return response()->json($order);
    }

    public function updateStatus(Request $request, RepairOrder $repairOrder)
    {
        $request->validate([
            'status' => 'required|in:reception,diagnosing,in_repair,finished',
            'send_completion_email' => 'boolean',
        ]);

        $oldStatus = $repairOrder->status;
        $newStatus = $request->status;

        $repairOrder->status = $newStatus;

        if ($newStatus === 'finished' && $oldStatus !== 'finished') {
            $repairOrder->completed_at = now();
            $repairOrder->save();

            foreach ($repairOrder->repairs as $repair) {
                if (!$repair->completed_at) {
                    $repair->completed_at = now();
                    $repair->save();
                }
            }

            if ($request->get('send_completion_email', false) && $repairOrder->client && $repairOrder->client->email) {
                $firstRepair = $repairOrder->repairs->first();
                if ($firstRepair) {
                    try {
                        Mail::to($repairOrder->client->email)
                            ->send(new RepairStatusNotification($firstRepair, true));
                    } catch (\Exception $e) {
                        \Log::error('Error al enviar email de completado: ' . $e->getMessage());
                    }
                }
            }

            return response()->json(['success' => true]);
        }

        $repairOrder->save();

        return response()->json(['success' => true]);
    }
} 