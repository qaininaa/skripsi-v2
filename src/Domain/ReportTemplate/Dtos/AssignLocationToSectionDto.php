<?php

namespace Domain\ReportTemplate\Dtos;

/**
 * Data Transfer Object for assigning a Location to a Section.
 */
class AssignLocationToSectionDto
{
    public string $location_id;
    public string $section_id;

    public function __construct(array $data)
    {
        $this->location_id = $data['location_id'];
        $this->section_id  = $data['section_id'];
    }
}
