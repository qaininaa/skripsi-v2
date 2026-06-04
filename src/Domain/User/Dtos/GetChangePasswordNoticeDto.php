<?php

namespace Domain\User\Dtos;

class GetChangePasswordNoticeDto
{
    public ?string $reason;

    public function __construct(array $data = [])
    {
        $this->reason = isset($data['reason']) && $data['reason'] !== '' ? (string) $data['reason'] : null;
    }
}
