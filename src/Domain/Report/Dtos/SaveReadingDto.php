<?php

namespace Domain\Report\Dtos;

/**
 * Reading-phase payload structure:
 *   sections[{instance_id}][rows][{location_row_id}][readings][{column_index}][reading_total]
 *   sections[{instance_id}][rows][{location_row_id}][readings][{column_index}][reading_fungi]
 */
class SaveReadingDto
{
    public array $sections;

    public function __construct(array $data)
    {
        $this->sections = $data['sections'] ?? [];
    }
}
