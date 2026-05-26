<?php

namespace Domain\Report\Dtos;

/**
 * Reading-phase payload structure:
 *   sections[{instance_id}][rows][{location_row_id}][readings][{column_index}][cfu_bacteri]
 *   sections[{instance_id}][rows][{location_row_id}][readings][{column_index}][cfu_fungsi]
 */
class SaveReadingDto
{
    public array $sections;

    public function __construct(array $data)
    {
        $this->sections = $data['sections'] ?? [];
    }
}
