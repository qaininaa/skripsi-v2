<?php

namespace Domain\ReportTemplate\Interfaces;

use Domain\ReportTemplate\Dtos\AssignLocationToSectionDto;
use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Domain\ReportTemplate\Dtos\UpdateSectionDto;
use Domain\ReportTemplate\Models\Section;

/**
 * Contract for Section data access.
 */
interface SectionRepositoryInterface
{
    /**
     * Persist a new section to the database.
     *
     * @param  CreateSectionDto  $data
     * @return Section
     */
    public function createSection(CreateSectionDto $data): Section;

    /**
     * Update an existing section.
     *
     * @param  Section           $section
     * @param  UpdateSectionDto  $data
     * @return void
     */
    public function updateSection(Section $section, UpdateSectionDto $data): void;

    /**
     * Delete a section from the database.
     *
     * @param  Section  $section
     * @return void
     */
    public function deleteSection(Section $section): void;

    /**
     * Assign a location to a section (set section_id on location).
     *
     * @param  AssignLocationToSectionDto  $data
     * @return void
     */
    public function assignLocation(AssignLocationToSectionDto $data): void;

    /**
     * Remove a location from a section (set section_id to null on location).
     *
     * @param  string  $locationId
     * @return void
     */
    public function removeLocation(string $locationId): void;

    /**
     * Get available (unassigned or same-type) locations for a section.
     * Filters by measurement_type matching the section's type.
     *
     * @param  Section  $section
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableLocations(Section $section);
}
