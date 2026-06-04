<?php

namespace Domain\ReportTemplate\Interfaces;

use Domain\ReportTemplate\Dtos\AssignLocationToSectionDto;
use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Domain\ReportTemplate\Dtos\UpdateSectionDto;
use Domain\ReportTemplate\Models\Section;
use Illuminate\Support\Collection;

/**
 * Contract for Section data access.
 */
interface SectionRepositoryInterface
{
    /**
     * Persist a new section to the database.
     */
    public function createSection(CreateSectionDto $data): Section;

    /**
     * Update an existing section.
     */
    public function updateSection(Section $section, UpdateSectionDto $data): void;

    /**
     * Find a section by id.
     */
    public function findById(string $id): ?Section;

    /**
     * Delete a section from the database.
     */
    public function deleteSection(Section $section): void;

    /**
     * Assign a location to a section (set section_id on location).
     */
    public function assignLocation(AssignLocationToSectionDto $data): void;

    /**
     * Remove a location from a section (set section_id to null on location).
     */
    public function removeLocation(string $locationId): void;

    /**
     * Get available (unassigned or same-type) locations for a section.
     * Filters by measurement_type matching the section's type.
     *
     * @return Collection
     */
    public function getAvailableLocations(Section $section);
}
