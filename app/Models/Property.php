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
        'featuredUntil'
    ];



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
}


