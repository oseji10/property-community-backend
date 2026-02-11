<?php
// app/Models/Favorite.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['userId', 'propertyId'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'userId');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'propertyId', 'property_id');
    }
}