<?php

namespace Domain\User\Dtos;

use Illuminate\Support\Carbon;

class CheckPasswordExpirationDto
{
    public function __construct(
        public readonly ?Carbon $passwordChangedAt,
        public readonly ?Carbon $checkedAt = null
    ) {
    }
}
