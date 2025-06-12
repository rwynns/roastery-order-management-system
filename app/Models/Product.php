<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sku',
        'price',
        'cost',
        'category_id',
        'image',
        'is_active',
        'track_stock',
        'min_stock_level'
    ];

    protected $cast = [
        'price' => 'decimal',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'min_stock_level' => 'integer'
    ];

    /**
     * Relationship: Product belongs to Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: Product has many OrderItems
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship: Product has one Inventory
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Products with low stock
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('inventory', function($q){
            $q->whereRaw('current_stock <= min_stock_level');
        });
    }

    /**
     * Get available stock from inventory
     */
    public function getAvailableStockAttribute()
    {
        return $this->inventory ? $this->inventory->available_stock : 0;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock($quantity = 1): bool
    {
        if (!$this->track_stock){
            return true;
        }

        return $this->available_stock >= $quantity;
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginAttribute()
    {
        if(!$this->cost || $this->cost == 0) {
            return 0;
        }

        return (($this->price - $this->cost) / $this->cost) *100;
    }
}
