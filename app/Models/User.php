<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use SoftDeletes;
    use HasApiTokens, Notifiable;

    public $table = 'users';
    protected $fillable = [
        'phoneNumber',
        'email',
        'role',
        'firstName',
        'lastName',
        'otherNames',
        'password', 
        'status',
        'otp_code',
        'otp_expires_at',
        'email_verified_at',
        'currentPlan'
    ];
    protected $dates = ['deleted_at'];
    protected $hidden = ['password'];


    public function getJWTIdentifier()
    {
        return $this->getKey(); // Returns the user's primary key (e.g., ID)
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // Add custom claims, e.g., role (pharmacist, doctor, etc.)
        ];
    }

    
    public function user_role()
    {
        return $this->belongsTo(Role::class, 'role', 'roleId'); 
    }


    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function lands()
    {
        return $this->hasMany(Land::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
