<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterSupplyReading extends Model
{
    protected $fillable = [
        'customer_id',
        'reading_date',
        'meter_reading',
        'previous_reading',
        'units_consumed',
        'amount_due',
        'payment_status',
        'payment_date',
        'month',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'payment_date' => 'date',
        'meter_reading' => 'decimal:2',
        'previous_reading' => 'decimal:2',
        'units_consumed' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WaterSupplyCustomer::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(WaterSupplyPayment::class, 'reading_id');
    }
}
