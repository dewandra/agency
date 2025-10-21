<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'token_version',
    ];

    protected $hidden = [
        'password',
        'token_version',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'token_version' => 'integer',
        'password' => 'hashed',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'role' => $this->role,
            'token_version' => $this->token_version,
        ];
    }

    // Relationships
    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    // Helper Methods
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isEditor(): bool
    {
        return $this->role === 'EDITOR';
    }

    public function incrementTokenVersion(): void
    {
        $this->increment('token_version');
    }
}
