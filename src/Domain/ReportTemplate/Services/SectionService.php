<?php

namespace Domain\ReportTemplate\Services;

use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;
use Domain\ReportTemplate\Dtos\AssignLocationToSectionDto;
use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Domain\ReportTemplate\Dtos\UpdateSectionDto;
use Domain\ReportTemplate\Interfaces\ReportTemplateRepositoryInterface;
use Domain\ReportTemplate\Interfaces\SectionRepositoryInterface;
use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\ReportTemplate\Models\Section;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        protected ReportTemplateRepositoryInterface $reportTemplateRepository,
    ) {
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Load a report template with its sections and their assigned locations.
     */
    public function getTemplateWithSections(ReportTemplate $reportTemplate): ReportTemplate
    {
        $template = $this->reportTemplateRepository->findByIdWithRelations($reportTemplate->id, [
            'mediumTemplates',
            'incubatorTemplates',
            'sections.locations.room',
        ]);

        if ($template === null) {
            throw (new ModelNotFoundException)->setModel(ReportTemplate::class, [$reportTemplate->id]);
        }

        return $template;
    }

    /**
     * Build the report template section management page data.
     *
     * @return array{reportTemplate: ReportTemplate, sectionAvailable: Collection}
     */
    public function getTemplateSectionData(string $reportTemplateId): array
    {
        $reportTemplate = $this->getTemplateWithSectionsById($reportTemplateId);

        $sectionAvailable = $reportTemplate->sections->mapWithKeys(
            fn ($section) => [$section->id => $this->getAvailableLocations($section)]
        );

        return [
            'reportTemplate' => $reportTemplate,
            'sectionAvailable' => $sectionAvailable,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    public function getTemplateWithSectionsById(string $reportTemplateId): ReportTemplate
    {
        $reportTemplate = $this->reportTemplateRepository->findByIdWithRelations($reportTemplateId, [
            'mediumTemplates',
            'incubatorTemplates',
            'sections.locations.room',
        ]);

        if ($reportTemplate === null) {
            throw (new ModelNotFoundException)->setModel(ReportTemplate::class, [$reportTemplateId]);
        }

        return $reportTemplate;
    }

    /**
     * Create a new section for a report template.
     */
    public function createSection(CreateSectionDto $dto): Section
    {
        return $this->repository->createSection($dto);
    }

    /**
     * Update an existing section.
     */
    public function updateSection(Section $section, UpdateSectionDto $dto): void
    {
        $this->repository->updateSection($section, $dto);
    }

    public function updateSectionById(string $reportTemplateId, string $sectionId, UpdateSectionDto $dto): void
    {
        $section = $this->findSectionForTemplate($reportTemplateId, $sectionId);

        $this->updateSection($section, $dto);
    }

    /**
     * Delete a section. Cascade on DB nullifies section_id on assigned locations.
     */
    public function deleteSection(Section $section): void
    {
        $this->repository->deleteSection($section);
    }

    public function deleteSectionById(string $reportTemplateId, string $sectionId): void
    {
        $this->deleteSection($this->findSectionForTemplate($reportTemplateId, $sectionId));
    }

    /**
     * Assign a location to a section.
     * Validates that the location's measurement_type matches the section's type.
     *
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
            'section_id' => $section->id,
        ]));
    }

    public function assignLocationById(string $reportTemplateId, string $sectionId, string $locationId): void
    {
        $this->assignLocation($this->findSectionForTemplate($reportTemplateId, $sectionId), $locationId);
    }

    /**
     * Remove a location from a section.
     *
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

    public function removeLocationById(string $reportTemplateId, string $sectionId, string $locationId): void
    {
        $this->removeLocation($this->findSectionForTemplate($reportTemplateId, $sectionId), $locationId);
    }

    /**
     * Get available locations for a section:
     * same measurement_type, unassigned OR already in this section.
     */
    public function getAvailableLocations(Section $section): Collection
    {
        return $this->repository->getAvailableLocations($section);
    }

    /**
     * @throws \RuntimeException
     */
    private function findSectionForTemplate(string $reportTemplateId, string $sectionId): Section
    {
        $section = $this->repository->findById($sectionId);
        if ($section === null || (string) $section->report_template_id !== $reportTemplateId) {
            throw new \RuntimeException('Section tidak ditemukan pada template laporan ini.');
        }

        return $section;
    }
}
