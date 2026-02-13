<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $table = 'payments';
    protected $primaryKey = 'paymentId';
    protected $fillable = [
        'propertyId', 'userId', 'paymentMethod', 'amount', 'transactionId', 'transactionReference', 'status', 'metaData', 'paymentGateway', 
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'userId', 'id'); 
    }

      public function property()
    {
        return $this->belongsTo(Property::class, 'propertyId', 'propertyId'); 
    }

   

      public function payment_for()
    {
        return $this->belongsTo(PaymentType::class, 'paymentFor', 'paymentTypeId'); 
    }
}