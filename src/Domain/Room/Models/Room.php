<?php

namespace Domain\Room\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'room_number',
        'class',
    ];
}