<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairTypeStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_type_id',
        'step_name',
        'step_order'
    ];

    public function repairType()
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class, 'step');
    }
}
