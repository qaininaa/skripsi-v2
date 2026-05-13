<?php

namespace Domain\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PasswordSetting extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'value'];
}
