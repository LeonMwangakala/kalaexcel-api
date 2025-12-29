<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstructionMaterial extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'description',
    ];
}
