<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionPackages extends Model
{
    use HasFactory;
    public $table = 'properties_promotion_packages';
    protected $primaryKey = 'packageId';
    protected $fillable = [
        'packageName',
        'packageDescription',
        'price',
        'durationDays',
        'promotionType'
    ];
    
}
