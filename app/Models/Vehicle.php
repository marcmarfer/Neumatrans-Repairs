<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Brand;
use App\Models\VehicleModel;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'plate_number',
        'brand',
        'model',
        'VIN',
        'motor_type',
        'added_at'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }
}
