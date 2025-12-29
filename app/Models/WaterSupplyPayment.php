<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BankAccount;

class WaterSupplyPayment extends Model
{
    protected $fillable = [
        'reading_id',
        'customer_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'bank_account_id',
        'bank_receipt',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function reading(): BelongsTo
    {
        return $this->belongsTo(WaterSupplyReading::class, 'reading_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WaterSupplyCustomer::class, 'customer_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
