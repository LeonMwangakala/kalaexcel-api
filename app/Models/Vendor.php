<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'location',
        'phone',
    ];

    public function constructionExpenses(): HasMany
    {
        return $this->hasMany(ConstructionExpense::class, 'vendor_id');
    }
}
