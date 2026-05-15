<?php

namespace Domain\PasswordPolicy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores application-wide password policy settings as key-value pairs.
 *
 * Known keys:
 * 'password_expiration_days' - Number of days before a password expires.
 * 'password_history_count'   - Number of previous passwords a user cannot reuse.
 *
 * @property string $id     
 * @property string $key    
 * @property string $value  
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PasswordSetting extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];
}
