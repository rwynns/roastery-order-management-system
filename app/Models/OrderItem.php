<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes'
    ];

    protected $cast = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relationship: OrderItem belongs to Order
     */
    public function order(): BelongTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: OrderItem belongs to Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total Price based on quantity and unit price
     */
    public function calculateTotalPrice()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        return $this->total_price;
    }

    /**
     * Boot method to automatically calculate total price
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $orderitem->calculateTotalPrice();
        });
    }
}
