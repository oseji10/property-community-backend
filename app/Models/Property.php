<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Property extends Model
{
    use HasFactory;
  
    public $table = 'properties';

    protected $primaryKey = 'propertyId';

    protected $fillable = [
        'propertyId',
        'propertyTypeId',
        'propertyTitle',
        'propertyDescription',
        'addedBy',
        'address',
        'city',
        'state',
        'price',
        'listingType',
        'bedrooms',
        'bathrooms',
        'garage',
        'longitude',
        'latitude',
        'otherFeatures',
        'amenities',
        'status',
        'slug',
        'currency',
        'isAvailable',
        'size',
        'views',
        'isFeatured',
        'featuredUntil',
        'average_rating',
        'total_ratings',
    ];



    public function ratings()
    {
        return $this->hasMany(PropertyRating::class, 'propertyId');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'addedBy', 'id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'propertyId', 'propertyId');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'currencyId');
    }

     public function property_type()
    {
        return $this->belongsTo(PropertyType::class, 'propertyTypeId', 'typeId');
    }

    public function getViewsAttribute($value)
    {
        return number_format($value);
    }

    public function messages()
{
    return $this->hasMany(Message::class)          // or Inquiry::class
                ->latest();                         // newest first
}

// Optional: only messages sent TO the owner (inquiries)
public function inquiries()
{
    return $this->hasMany(Message::class, 'receiverId', 'addedBy')
                ->where('receiverId', $this->userId) // if you have receiver_id
                ->latest();
}

public function favoritedBy()
{
    return $this->belongsToMany(
        User::class,           // related model
        'favorites',  // pivot table name
        'propertyId',         // foreign key for THIS model (Property) in pivot table
        'userId'              // foreign key for the RELATED model (User) in pivot table
    )
    ->withTimestamps();
}


public function propertyViews()
{
    return $this->hasMany(PropertyView::class, 'property_id', 'propertyId');
}

    /**
     * Indicates if the model should use snake_case for attributes.
     * Set to false to prevent Laravel from converting userId to user_id
     */
    public static $snakeAttributes = false;

   


    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'propertyId' => 'integer',
        'currencyId' => 'integer',
    ];

    /**
     * Get the user that owns the property
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

 



    /**
     * Get the promotion package for this property
     */
    public function promotionPackage()
    {
        return $this->belongsTo(PromotionPackage::class, 'promotionPackageId', 'packageId');
    }




    /**
     * Scope to filter by listing type
     */
    public function scopeForSale($query)
    {
        return $query->where('listingType', 'sale');
    }

    /**
     * Scope to filter by listing type
     */
    public function scopeForRent($query)
    {
        return $query->where('listingType', 'rent');
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}


