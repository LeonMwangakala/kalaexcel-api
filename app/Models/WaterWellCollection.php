<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\BankAccount;

class WaterWellCollection extends Model
{
    protected $fillable = [
        'date',
        'buckets_sold',
        'unit_price',
        'total_amount',
        'operator_id',
        'bank_account_id',
        'deposit_id',
        'deposit_date',
        'is_deposited',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'deposit_date' => 'date',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'buckets_sold' => 'integer',
        'is_deposited' => 'boolean',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
