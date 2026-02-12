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
        return $this->belongsTo(User::class, 'senderId', 'id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiverId', 'id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'propertyId', 'propertyId');
    }
}