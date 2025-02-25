<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairStepStatus extends Model
{
    use HasFactory;

    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function repairTypeStep()
    {
        return $this->belongsTo(RepairTypeStep::class);
    }
}
