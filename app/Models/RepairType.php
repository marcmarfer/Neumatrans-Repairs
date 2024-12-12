<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairType extends Model
{
    use HasFactory;

    public function repair()
    {
        return $this->hasMany(Repair::class);
    }

    public function repairTypeStep()
    {
        return $this->hasMany(RepairTypeStep::class);
    }
}
