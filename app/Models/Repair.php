<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairStepStatuses()
    {
        return $this->hasMany(RepairStepStatus::class);
    }
}
