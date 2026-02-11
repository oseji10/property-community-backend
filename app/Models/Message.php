<?php
// app/Models/Message.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'senderId',
        'receiverId',
        'propertyId',
        'content',
        'isRead',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'id', 'senderId');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'id', 'receiverId');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'propertyId', 'property_id');
    }
}