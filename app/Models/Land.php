<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Land extends Model
{
    use HasFactory;
  
    public $table = 'lands';
    protected $primaryKey = 'landId';

    protected $fillable = [
        'landId',
        'typeId',
        'landName',
        'landDescription',
        'addedBy',
        'address',
        'city',
        'state',
        'price',
        'listingType',
        'bedrooms',
        'bathrooms',
        'isAvailable'
    ];



    public function owner()
    {
        return $this->belongsTo(User::class, 'id', 'userId');
    }

    public function land_images()
    {
        return $this->hasMany(LandImage::class, 'landId', 'landId');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}


