<?php

namespace Domain\ReportTemplate\Dtos;

class CreateReportTemplateDto
{
    public string $name;
    public int $annex_number;
    public string $sop_code;
    public string $sop_version;
    public bool $has_personnel;

    /** @var array<int, array{name: string}> */
    public array $medium_templates;

    /** @var array<int, array{label: string, min_day: int}> */
    public array $incubator_templates;

    public function __construct(array $data)
    {
        $this->name                = $data['name'];
        $this->annex_number        = (int) $data['annex_number'];
        $this->sop_code            = $data['sop_code'];
        $this->sop_version         = $data['sop_version'];
        $this->has_personnel       = (bool) ($data['has_personnel'] ?? false);
        $this->medium_templates    = $data['medium_templates'] ?? [];
        $this->incubator_templates = $data['incubator_templates'] ?? [];
    }
}
