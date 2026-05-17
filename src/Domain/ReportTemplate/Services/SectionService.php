<?php

namespace Domain\ReportTemplate\Services;

use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;
use Domain\ReportTemplate\Dtos\AssignLocationToSectionDto;
use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Domain\ReportTemplate\Dtos\UpdateSectionDto;
use Domain\ReportTemplate\Interfaces\SectionRepositoryInterface;
use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\ReportTemplate\Models\Section;
use Illuminate\Support\Collection;

/**
 * Handles business logic for the Section domain.
 */
class SectionService
{
    protected SectionRepositoryInterface $repository;
    protected LocationRepositoryInterface $locationRepository;

    public function __construct(
        SectionRepositoryInterface $repository,
        LocationRepositoryInterface $locationRepository,
    ) {
        $this->repository         = $repository;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Load a report template with its sections and their assigned locations.
     *
     * @param  ReportTemplate  $reportTemplate
     * @return ReportTemplate
     */
    public function getTemplateWithSections(ReportTemplate $reportTemplate): ReportTemplate
    {
        return $reportTemplate->load([
            'mediumTemplates',
            'incubatorTemplates',
            'sections.locations.room',
        ]);
    }

    /**
     * Create a new section for a report template.
     *
     * @param  CreateSectionDto  $dto
     * @return Section
     */
    public function createSection(CreateSectionDto $dto): Section
    {
        return $this->repository->createSection($dto);
    }

    /**
     * Update an existing section.
     *
     * @param  Section           $section
     * @param  UpdateSectionDto  $dto
     * @return void
     */
    public function updateSection(Section $section, UpdateSectionDto $dto): void
    {
        $this->repository->updateSection($section, $dto);
    }

    /**
     * Delete a section. Cascade on DB nullifies section_id on assigned locations.
     *
     * @param  Section  $section
     * @return void
     */
    public function deleteSection(Section $section): void
    {
        $this->repository->deleteSection($section);
    }

    /**
     * Assign a location to a section.
     * Validates that the location's measurement_type matches the section's type.
     *
     * @param  Section  $section
     * @param  string   $locationId
     * @return void
     *
     * @throws \RuntimeException
     */
    public function assignLocation(Section $section, string $locationId): void
    {
        $location = $this->locationRepository->findOrFail($locationId);

        if ($location->measurement_type !== $section->measurement_type) {
            throw new \RuntimeException(
                'Tipe pengukuran lokasi tidak sesuai dengan tipe section ini.'
            );
        }

        if ($location->section_id !== null && $location->section_id !== $section->id) {
            throw new \RuntimeException(
                'Lokasi ini sudah ditugaskan ke section lain.'
            );
        }

        $this->repository->assignLocation(new AssignLocationToSectionDto([
            'location_id' => $locationId,
            'section_id'  => $section->id,
        ]));
    }

    /**
     * Remove a location from a section.
     *
     * @param  Section  $section
     * @param  string   $locationId
     * @return void
     *
     * @throws \RuntimeException
     */
    public function removeLocation(Section $section, string $locationId): void
    {
        $location = $this->locationRepository->findOrFail($locationId);

        if ($location->section_id !== $section->id) {
            throw new \RuntimeException(
                'Lokasi ini tidak terdaftar di section ini.'
            );
        }

        $this->repository->removeLocation($locationId);
    }

    /**
     * Get available locations for a section:
     * same measurement_type, unassigned OR already in this section.
     *
     * @param  Section  $section
     * @return Collection
     */
    public function getAvailableLocations(Section $section): Collection
    {
        return $this->repository->getAvailableLocations($section);
    }
}
