<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandImage extends Model
{
    protected $table = 'land_images';
    protected $fillable = [
        'imageId',
        'landId',
        'imageUrl',
    ];
    protected $primaryKey = 'imageId';

     public function land()
    {
        return $this->belongsTo(Land::class, 'landId', 'landId');
    }
    
}
