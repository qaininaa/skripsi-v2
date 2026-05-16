<?php

namespace Domain\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores hashed previous passwords for a user to enforce the password reuse policy.
 *
 * @property string $id        
 * @property string $user_id   
 * @property string $password 
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PasswordHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Get the user who owns this password history entry.
     *
     * @return BelongsTo<User, PasswordHistory>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
