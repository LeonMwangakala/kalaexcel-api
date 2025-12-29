<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionExpense extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'material_id',
        'quantity',
        'unit_price',
        'amount',
        'date',
        'vendor_id',
        'bank_account_id',
        'description',
        'receipt_url',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ConstructionProject::class, 'project_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(ConstructionMaterial::class, 'material_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
