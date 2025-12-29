<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'previous_balance',
        'date',
        'description',
        'category',
        'reference',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }
}
