<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_type_id',
        'vehicle_id',
        'repair_order_id',
        'observations',
        'tracking_token',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
