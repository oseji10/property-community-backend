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
        'size'
    ];



    public function owner()
    {
        return $this->belongsTo(User::class, 'id', 'userId');
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
}


