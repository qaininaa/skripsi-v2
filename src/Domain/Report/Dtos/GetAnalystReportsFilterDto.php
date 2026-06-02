<?php

namespace Domain\Report\Dtos;

class GetAnalystReportsFilterDto
{
    /** all|belum_dikerjakan|sedang_dimonitoring|sedang_dibaca|dikirim|dikembalikan */
    public string $tab;

    /** ID of the analyst viewing the list (used to scope "milik saya"). */
    public ?string $analyst_id;

    public function __construct(array $data = [])
    {
        $this->tab        = isset($data['tab']) && $data['tab'] !== '' ? (string) $data['tab'] : 'all';
        $this->analyst_id = isset($data['analyst_id']) && $data['analyst_id'] !== '' ? (string) $data['analyst_id'] : null;
    }
}
