<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comune extends Model
{
    protected $table = 'comuni';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'postal_codes' => 'array',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
