<?php

namespace Domain\User\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Represents an application user.
 *
 * @property string  $id                  
 * @property string  $name                 
 * @property string  $username            
 * @property string  $password            
 * @property string  $role                 
 * @property Carbon  $password_changed_at  
 * @property Carbon  $updated_at
 */
class User extends Authenticatable
{
    use HasUuids, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'password_changed_at' => 'datetime',
    ];

    /**
     * Get the user's password history entries.
     *
     * @return HasMany<PasswordHistory>
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
