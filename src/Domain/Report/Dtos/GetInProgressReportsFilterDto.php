<?php

namespace Domain\Report\Dtos;

class GetInProgressReportsFilterDto
{
    public string $stage;

    public int $step;

    public string $userId;

    public function __construct(array $data)
    {
        $this->stage = isset($data['stage']) && $data['stage'] !== '' ? (string) $data['stage'] : 'all';
        $this->step = (int) ($data['step'] ?? 2);
        $this->userId = (string) ($data['user_id'] ?? '');
    }
}
