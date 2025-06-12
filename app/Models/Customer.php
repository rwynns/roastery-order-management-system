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
        'is_active'
    ];

    protected $cast = [
        'date_of_birth' => 'date',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
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
            'total_spent' => $completedOrders->sum('total_amount')
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
            return 'silver';
        }
        
        return 'Regular';
    }
}
