<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the user who viewed the property
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the property that was viewed
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'propertyId');
    }
}