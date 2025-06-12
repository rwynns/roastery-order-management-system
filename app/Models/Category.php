<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active',
    ];

    protected $cast = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship : Category has many Products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $this->where('is_active',true);
    }

    /**
     * Get products count for this category
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }
}
