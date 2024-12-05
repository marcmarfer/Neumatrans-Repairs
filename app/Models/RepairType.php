<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairType extends Model
{
    use HasFactory;

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }

    public function repairTypeSteps()
    {
        return $this->hasMany(RepairTypeStep::class);
    }
}
