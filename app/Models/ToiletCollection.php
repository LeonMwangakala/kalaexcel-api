<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BankAccount;

class ToiletCollection extends Model
{
    protected $fillable = [
        'date',
        'total_users',
        'amount_collected',
        'cashier_id',
        'bank_account_id',
        'deposit_id',
        'deposit_date',
        'is_deposited',
        'notes',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    protected $casts = [
        'date' => 'date',
        'deposit_date' => 'date',
        'amount_collected' => 'decimal:2',
        'is_deposited' => 'boolean',
        'total_users' => 'integer',
    ];
}
