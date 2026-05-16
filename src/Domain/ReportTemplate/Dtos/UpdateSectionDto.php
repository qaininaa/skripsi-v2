<?php

namespace Domain\ReportTemplate\Dtos;

/**
 * Data Transfer Object for updating an existing Section.
 */
class UpdateSectionDto
{
    public string $measurement_unit;
    public string $measurement_type;
    public int $max_column;
    public ?string $column_label;
    public string $time_slot_type;
    public bool $has_machine_setup;
    public int $order;

    public function __construct(array $data)
    {
        $this->measurement_unit  = $data['measurement_unit'];
        $this->measurement_type  = $data['measurement_type'];
        $this->max_column        = (int) ($data['max_column'] ?? 1);
        $this->column_label      = $data['column_label'] ?? null;
        $this->time_slot_type    = $data['time_slot_type'];
        $this->has_machine_setup = (bool) ($data['has_machine_setup'] ?? false);
        $this->order             = (int) ($data['order'] ?? 1);
    }
}
