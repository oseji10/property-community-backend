<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Transaction extends Model
{
    use HasFactory;
  

    public $table = 'transactions';
    protected $fillable = [
         'transactionType','amount','status','reference',
            'userId','transactionable_type','transactionable_id'
    ];
    protected $primaryKey = 'transactionId';

     public function transactionable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

 

}
