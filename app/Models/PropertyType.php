<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PropertyType extends Model
{
    use HasFactory;
  
    public $table = 'property_types';
    protected $primaryKey = 'typeId';

    protected $fillable = [
        'typeId',
        'typeName',
        'typeDescription'
    ];



}


