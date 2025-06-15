<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'current_stock',
        'reserved_stock',
        'last_cost',
        'last_updated_at'
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'last_cost' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Relationship: Inventory belongs to product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get available stock (computed atttribute handled by database)
     */
    public function getAvailableStockAttribute()
    {
        return $this->current_stock - $this->reserved_stock;
    }

    /**
     * Scope: Low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('product', function($q){
            $q->whereRaw('inventory.current_stock <= products.min_stock_leves');
        });
    }

    /**
     * Add Stock
     */
    public function addStock(int $quantity, ?float $cost = null)
    {
        $this->increment('current_stock',$quantity);
            if ($cost){
                $this->last_cost = $cost;
            }
            $this->last_updated_at = now();
            $this->save();
    }

    /**
     * Reduce stock
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->available_stock >= $quantity) {
            $this->decrement('current_stock', $quantity);
            $this->last_updated_at = now();
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Reverse Stock
     */
    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock >= $quantity) {
            $this->increment('reserved_stock', $quantity);
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Release reversed stock
     */
    public function releaseReservedStock(int $quantity)
    {
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        $this->save();
    }
}
