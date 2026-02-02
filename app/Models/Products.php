<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    public $table = 'lands';
    protected $fillable = [
        'landId',
        'landName',
        'landType',
        'price',
        'addedBy',
        'isAvailable',
    ];
    protected $primaryKey = 'landId';

  public function owner()
    {
        return $this->belongsTo(User::class, 'addedBy', 'id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
   
}
