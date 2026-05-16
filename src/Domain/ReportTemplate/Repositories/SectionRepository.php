<?php

namespace Domain\ReportTemplate\Repositories;

use Domain\Location\Models\Location;
use Domain\ReportTemplate\Dtos\AssignLocationToSectionDto;
use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Domain\ReportTemplate\Dtos\UpdateSectionDto;
use Domain\ReportTemplate\Interfaces\SectionRepositoryInterface;
use Domain\ReportTemplate\Models\Section;

class SectionRepository implements SectionRepositoryInterface
{
    /**
     * Persist a new section to the database.
     *
     * @param  CreateSectionDto  $data
     * @return Section
     */
    public function createSection(CreateSectionDto $data): Section
    {
        $section = new Section();
        $section->report_template_id = $data->report_template_id;
        $section->measurement_unit   = $data->measurement_unit;
        $section->measurement_type   = $data->measurement_type;
        $section->max_column         = $data->max_column;
        $section->column_label       = $data->column_label;
        $section->time_slot_type     = $data->time_slot_type;
        $section->has_machine_setup  = $data->has_machine_setup;
        $section->save();

        return $section;
    }

    /**
     * Update an existing section.
     *
     * @param  Section           $section
     * @param  UpdateSectionDto  $data
     * @return void
     */
    public function updateSection(Section $section, UpdateSectionDto $data): void
    {
        $section->measurement_unit  = $data->measurement_unit;
        $section->measurement_type  = $data->measurement_type;
        $section->max_column        = $data->max_column;
        $section->column_label      = $data->column_label;
        $section->time_slot_type    = $data->time_slot_type;
        $section->has_machine_setup = $data->has_machine_setup;
        $section->save();
    }

    /**
     * Delete a section. Cascade on DB will nullify location section_id.
     *
     * @param  Section  $section
     * @return void
     */
    public function deleteSection(Section $section): void
    {
        $section->delete();
    }

    /**
     * Assign a location to a section by setting section_id and section_assigned_at.
     *
     * @param  AssignLocationToSectionDto  $data
     * @return void
     */
    public function assignLocation(AssignLocationToSectionDto $data): void
    {
        Location::where('id', $data->location_id)->update([
            'section_id'          => $data->section_id,
            'section_assigned_at' => now(),
        ]);
    }

    /**
     * Remove a location from a section by nullifying section_id.
     *
     * @param  string  $locationId
     * @return void
     */
    public function removeLocation(string $locationId): void
    {
        Location::where('id', $locationId)->update([
            'section_id'          => null,
            'section_assigned_at' => null,
        ]);
    }

    /**
     * Get locations that match the section's measurement_type and are either
     * unassigned (section_id is null) or already assigned to this section.
     *
     * @param  Section  $section
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableLocations(Section $section)
    {
        return Location::query()
            ->with('room')
            ->where('measurement_type', $section->measurement_type)
            ->whereNull('section_id')
            ->join('rooms', 'locations.room_id', '=', 'rooms.id')
            ->orderBy('rooms.name')
            ->orderBy('locations.loc_number')
            ->select('locations.*')
            ->get();
    }
}
