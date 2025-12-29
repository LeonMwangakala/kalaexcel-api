<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'contract_number',
        'tenant_id',
        'property_id',
        'rent_amount',
        'start_date',
        'end_date',
        'terms',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rent_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rentPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RentPayment::class, 'contract_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $lastContract = static::orderBy('id', 'desc')->first();
                $nextNumber = $lastContract ? ((int) substr($lastContract->contract_number, 3)) + 1 : 1;
                $contract->contract_number = 'CON' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
