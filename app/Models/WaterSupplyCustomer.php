<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterSupplyCustomer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'location',
        'meter_number',
        'starting_reading',
        'unit_price',
        'status',
        'date_registered',
    ];

    protected $casts = [
        'date_registered' => 'date',
        'starting_reading' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function readings(): HasMany
    {
        return $this->hasMany(WaterSupplyReading::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(WaterSupplyPayment::class, 'customer_id');
    }
}
