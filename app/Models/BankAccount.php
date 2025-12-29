<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'account_name',
        'bank_name',
        'branch_name',
        'account_number',
        'opening_balance',
        'type',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'account_id');
    }
}
