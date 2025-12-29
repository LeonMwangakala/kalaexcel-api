<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'tax_id',
        'registration_number',
        'logo',
    ];
}
