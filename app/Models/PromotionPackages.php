<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionPackages extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'properties_promotion_packages';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'packageId';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'packageName',
        'packageDescription',
        'price',
        'durationDays',
        'promotionType',
        'isActive',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'durationDays' => 'integer',
        'isActive' => 'boolean',
    ];

    /**
     * Get all properties using this package
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'promotionPackageId', 'packageId');
    }

    /**
     * Scope to get only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope to filter by promotion type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('promotionType', $type);
    }

    /**
     * Scope to search packages
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('packageName', 'LIKE', "%{$search}%")
                     ->orWhere('packageDescription', 'LIKE', "%{$search}%");
    }

    /**
     * Get formatted price attribute
     */
    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }
}