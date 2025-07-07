<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'total_spent',
        'total_orders',
        'last_order_at',
        'is_active'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'last_order_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Relationship: Customer has many Orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope: Only ative customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get last order for this customer
     */
    public function getLastOrderAttribute()
    {
        return $this->orders()->latest()->first();
    }

    /**
     * Update customer statistics
     */
    public function updateStatistics()
    {
        $completedOrders = $this->orders()->where('status', 'completed')->get();

        $this->update([
            'total_orders' => $completedOrders->count(),
            'total_spent' => $completedOrders->sum('total_amount'),
            'last_order_at' => $completedOrders->isNotEmpty() ? $completedOrders->first()->created_at : null
        ]);
    }

    /**
     * Get customer tier based on total spent
     */
    public function getTierAttribute()
    {
        if ($this->total_spent >= 1000000){
            return 'VIP';
        } elseif ($this->total_spent >= 500000){
            return 'Gold';
        } elseif ($this->total_spent >= 100000){
            return 'Silver';
        }
        
        return 'Regular';
    }

    /**
     * Get customer purchase frequency per month
     */
    public function getPurchaseFrequencyAttribute()
    {
        if ($this->total_orders == 0) {
            return 0;
        }

        $firstOrder = $this->orders()->oldest()->first();
        if (!$firstOrder){
            return 0;
        }

        $monthsSinceFirst = now()->diffInMonths($firstOrder->created_at) +1;
        return round($this->total_orders / $monthsSinceFirst, 2);
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValueAttribute()
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        return round($this->total_spent / $this->total_orders, 2);
    }
}
