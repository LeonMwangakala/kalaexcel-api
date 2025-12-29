<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionProject extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'budget',
        'total_spent',
        'status',
        'progress',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'progress' => 'integer',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(ConstructionExpense::class, 'project_id');
    }
}
