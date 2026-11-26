<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_id' => 'integer',
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->role !== null) {
                return;
            }

            $user->role = $user->company_id === null
                ? UserRole::GlobalAdmin
                : UserRole::CompanyAdmin;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isGlobalAdmin(): bool
    {
        return $this->hasRole(UserRole::GlobalAdmin);
    }

    public function isCompanyAdmin(): bool
    {
        return $this->hasRole(UserRole::CompanyAdmin);
    }

    public function isMember(): bool
    {
        return $this->hasRole(UserRole::Member);
    }

    public function isAdmin(): bool
    {
        return $this->isGlobalAdmin() || $this->isCompanyAdmin();
    }

    public function isGlobal(): bool
    {
        return $this->isGlobalAdmin() && $this->company_id === null;
    }
}
