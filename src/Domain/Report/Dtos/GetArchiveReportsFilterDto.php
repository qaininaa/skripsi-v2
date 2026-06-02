<?php

namespace Domain\Report\Dtos;

class GetArchiveReportsFilterDto
{
    public ?string $folder;
    public ?string $search;

    public function __construct(array $data = [])
    {
        $this->folder = isset($data['folder']) && $data['folder'] !== '' ? (string) $data['folder'] : null;
        $this->search = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
    }
}
