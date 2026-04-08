<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PropertyRating extends Model
{
    public $table = 'property_ratings';
    protected $fillable = ['userId', 'propertyId', 'rating'];
}