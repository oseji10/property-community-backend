<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'currentPlan',
        // Additional fields for profile
        'address',
        'city',
        'state',
        'country',
        'avatar',
        'bio',
        'company',
        'website',
    ];
    
    protected $dates = ['deleted_at'];
    protected $hidden = ['password'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Returns the user's primary key (e.g., ID)
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // Add custom claims, e.g., role
        ];
    }

    /**
     * Get the user's role relationship.
     */
    public function user_role()
    {
        return $this->belongsTo(Role::class, 'role', 'roleId'); 
    }

    /**
     * Get the user's role relationship (alias).
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role', 'roleId'); 
    }

    /**
     * Get the properties owned by the user.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'addedBy', 'id');
    }

    /**
     * Get the lands owned by the user.
     */
    public function lands(): HasMany
    {
        return $this->hasMany(Land::class);
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the favorites for the user.
     * Creates a many-to-many relationship with properties.
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class, 
            'user_favorites', 
            'userId', 
            'propertyId'
        )->withTimestamps();
    }

    /**
     * Get the saved properties for the user.
     */
    public function savedProperties(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class, 
            'user_saved_properties', 
            'userId', 
            'propertyId'
        )->withTimestamps();
    }

    /**
     * Get the property views for the user.
     */
    public function propertyViews(): HasMany
    {
        return $this->hasMany(PropertyView::class, 'userId', 'id');
    }

    /**
     * Get the inquiries made by the user.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'userId', 'id');
    }

    /**
     * Get the promotion packages purchased by the user.
     */
    public function promotionPackages(): BelongsToMany
    {
        return $this->belongsToMany(
            PromotionPackages::class,
            'user_promotion_packages',
            'userId',
            'packageId'
        )->withPivot('propertyId', 'startDate', 'endDate', 'status')
         ->withTimestamps();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user is a regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim($this->firstName . ' ' . $this->lastName);
        return $name ?: $this->email;
    }

    /**
     * Get the avatar URL attribute.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Check if it's a full URL or a local path
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }
        
        // Generate avatar from name using UI Avatars API
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->fullName) . '&background=random&size=128';
    }

    /**
     * Get user's property count.
     */
    public function getPropertiesCountAttribute(): int
    {
        return $this->properties()->count();
    }

    /**
     * Get user's active properties count.
     */
    public function getActivePropertiesCountAttribute(): int
    {
        return $this->properties()->where('status', 'available')->count();
    }

    /**
     * Get user's total views across all properties.
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->properties()->sum('views');
    }

    /**
     * Get user's favorites count.
     */
    public function getFavoritesCountAttribute(): int
    {
        try {
            return $this->favorites()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Scope to get users with their properties count.
     */
    public function scopeWithPropertiesCount($query)
    {
        return $query->withCount('properties');
    }

    /**
     * Scope to search users.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('firstName', 'LIKE', "%{$search}%")
                     ->orWhere('lastName', 'LIKE', "%{$search}%")
                     ->orWhere('email', 'LIKE', "%{$search}%")
                     ->orWhere('phoneNumber', 'LIKE', "%{$search}%")
                     ->orWhere('otherNames', 'LIKE', "%{$search}%");
    }

    /**
     * Scope to filter by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}