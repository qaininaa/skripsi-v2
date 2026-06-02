<?php

namespace Domain\Report\Dtos;

class UpdateReportDto
{
    public string $report_template_id;
    public string $product_name;
    public string $batch_number;

    public function __construct(array $data)
    {
        $this->report_template_id = $data['report_template_id'];
        $this->product_name       = $data['product_name'];
        $this->batch_number       = $data['batch_number'];
    }
}
