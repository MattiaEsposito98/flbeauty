<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use HasUniqueSlug;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'variant_label',
        'images',
        'video',
        'price',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'images' => 'array',
        ];
    }

    public function getCoverImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Tutte le varianti (anche quelle disattivate): le gestisce l'admin. */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Le varianti che il cliente può vedere e ordinare. */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /** Il prodotto si vende "a varianti" se ne ha almeno una attiva: la scorta sta lì. */
    public function hasVariants(): bool
    {
        return $this->relationLoaded('activeVariants')
            ? $this->activeVariants->isNotEmpty()
            : $this->activeVariants()->exists();
    }

    /**
     * Con le varianti la disponibilità del prodotto è la somma di quelle delle varianti attive:
     * così catalogo, "esaurito" e ricerca continuano a leggere un solo numero (products.stock).
     */
    public function syncStockFromVariants(): void
    {
        if (! $this->variants()->exists()) {
            return;
        }

        $total = (int) $this->activeVariants()->sum('stock');

        if ((int) $this->stock !== $total) {
            $this->forceFill(['stock' => $total])->saveQuietly();
        }
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }
}
