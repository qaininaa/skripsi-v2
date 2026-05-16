<?php

namespace Domain\Report\Dtos;

class GetReportsFilterDto
{
    public ?string $search;
    public ?string $status;

    public function __construct(array $data = [])
    {
        $this->search = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
        $this->status = isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null;
    }
}
