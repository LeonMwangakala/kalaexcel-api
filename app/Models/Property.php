<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'name',
        'property_type_id',
        'location_id',
        'size',
        'status',
        'monthly_rent',
        'date_added',
    ];

    protected $casts = [
        'date_added' => 'date',
        'monthly_rent' => 'decimal:2',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_property')->withTimestamps();
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->exists();
    }
}
