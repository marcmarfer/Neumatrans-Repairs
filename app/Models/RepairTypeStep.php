<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairTypeStep extends Model
{
    use HasFactory;

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairStepStatuses()
    {
        return $this->hasMany(RepairStepStatus::class);
    }
}
