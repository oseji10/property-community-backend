<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['fullname', 'email', 'mobile', 'message', 'ip_address'];
}