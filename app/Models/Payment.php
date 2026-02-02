<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $table = 'payments';
    // protected $primaryKey = 'resultId';
    protected $fillable = [
        'applicationId', 'userId', 'rrr', 'amount', 'orderId', 'status', 'response', 'channel', 'paymentDate'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'userId', 'id'); 
    }

      public function applications()
    {
        return $this->belongsTo(Applications::class, 'applicationId', 'applicationId'); 
    }

   

      public function payment_for()
    {
        return $this->belongsTo(PaymentType::class, 'paymentFor', 'paymentTypeId'); 
    }
}