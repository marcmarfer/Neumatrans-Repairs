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
        'observations',
        'status',
        'started_at'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairStepStatus()
    {
        return $this->hasMany(RepairStepStatus::class);
    }
}
