<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'DNI',
        'name',
        'email',
        'telephone',
        'city',
        'postal_code',
        'registered_at',
        'country',
    ];

    public function vehicle()
    {
        return $this->hasMany(Vehicle::class);
    }
}
