<?php

namespace Domain\ReportTemplate\Dtos;

class UpdateReportTemplateDto
{
    public string $name;
    public int $annex_number;
    public string $sop_code;
    public string $sop_version;

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
        $this->medium_templates    = $data['medium_templates'] ?? [];
        $this->incubator_templates = $data['incubator_templates'] ?? [];
    }
}
