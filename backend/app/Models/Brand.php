<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marca del prodotto (facoltativa). Non ha una voce di menu nel pannello: si crea al
 * volo dal menu "Marca" del prodotto (pulsante +), come la categoria.
 */
class Brand extends Model
{
    use HasFactory;
    use HasUniqueSlug;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
