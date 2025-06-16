<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_status',
        'notes',
        'completed_at',
    ];

    protected $cast = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime'
    ];

    /**
     * Relationship: Order belongs to Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belonsTo(Customer::class);
    }

    /**
     * Relationship: Order belongs to User (cashier/staff)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship Order has many OrderItems
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship: Order has many Payments
     */
    public function payments():  HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope: Orders by Status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope : Completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status','completed');
    }

    /**
     * Scope: Today's orders
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at',today());
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): String
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $sequence = str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $sequence;
    }
    
    /**
     * Calculate order totals
     */
    public function calculateTotals()
    {
        $subtotal = $this->orderItems()->sum('total_price');
        $taxAmount = $subtotal * 0.1; // 10% tax
        $totalAmount = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount'=> $totalAmount
        ]);
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute()
    {
        return $this->payment()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }

    /**
     * Check if order is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'completed_at' => now()
        ]);

        //Update customer statistics
        if ($this->customer){
            $this->customer->updateStatistics();
        }
    }
}
