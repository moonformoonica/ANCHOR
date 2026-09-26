<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralInstitution extends Model
{
    protected $fillable = [
        'name',
        'category',
        'contact_channel',
        'region_scope',
        'status',
        'last_verified_date',
        'rationale',
        'notes',
    ];

    protected function casts(): array
    {
        return ['last_verified_date' => 'date'];
    }
}