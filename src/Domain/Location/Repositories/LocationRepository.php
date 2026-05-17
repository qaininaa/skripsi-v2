<?php

namespace Domain\Location\Repositories;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;

/**
 * Eloquent implementation of LocationRepositoryInterface.
 *
 * All database access for the Location domain goes through this class.
 */
class LocationRepository implements LocationRepositoryInterface
{
    /**
     * Retrieve a paginated list of locations with optional search and room filter.
     *
     * Results are joined with the rooms table and ordered by room name then loc_number.
     *
     * @param  GetLocationsFilterDto  $data  Filter parameters (search, room_id).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocations(GetLocationsFilterDto $data)
    {
        return Location::query()
            ->with('room')
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($subQuery) use ($data) {
                    $subQuery->where('loc_number', 'like', '%' . $data->search . '%')
                        ->orWhereHas('room', function ($roomQuery) use ($data) {
                            $roomQuery->where('name', 'like', '%' . $data->search . '%');
                        });
                });
            })
            ->when($data->room_id !== null, function ($query) use ($data) {
                $query->where('room_id', $data->room_id);
            })
            ->join('rooms', 'locations.room_id', '=', 'rooms.id')
            ->orderBy('rooms.name')
            ->orderBy('locations.loc_number')
            ->select('locations.*')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Find a location by room ID and location number (case-insensitive).
     *
     * Optionally excludes a specific location ID to support update uniqueness checks.
     * Returns null if both room_id and loc_number are null.
     *
     * @param  GetLocationDto  $data  DTO with room_id, loc_number, and optional excludeId.
     * @return Location|null          The matching location, or null if not found.
     */
    public function getLocationByRoomAndNumber(GetLocationDto $data): ?Location
    {
        if ($data->room_id === null && $data->loc_number === null) {
            return null;
        }

        $normalizedLocNumber = strtolower(trim($data->loc_number ?? ''));

        return Location::query()
            ->where('room_id', $data->room_id)
            ->whereRaw('LOWER(loc_number) = ?', [$normalizedLocNumber])
            ->when($data->excludeId !== null, fn ($q) => $q->where('id', '!=', $data->excludeId))
            ->first();
    }

    /**
     * Persist a new location to the database.
     *
     * @param  CreateLocationDto  $data  Data for the new location.
     * @return Location                  The newly created location model.
     */
    public function createLocation(CreateLocationDto $data): Location
    {
        $location = new Location();
        $location->room_id            = $data->room_id;
        $location->frequency          = $data->frequency;
        $location->loc_number         = $data->loc_number;
        $location->measurement_type   = $data->measurement_type;
        $location->alert_limit_total  = $data->alert_limit_total;
        $location->alert_limit_fungi  = $data->alert_limit_fungi;
        $location->alert_action_total = $data->alert_action_total;
        $location->alert_action_fungi = $data->alert_action_fungi;
        $location->save();

        return $location;
    }

    /**
     * Update an existing location with new data.
     *
     * @param  Location           $location  The location model to update.
     * @param  UpdateLocationDto  $data      New values to apply.
     * @return void
     */
    public function updateLocation(Location $location, UpdateLocationDto $data): void
    {
        $location->room_id            = $data->room_id;
        $location->frequency          = $data->frequency;
        $location->loc_number         = $data->loc_number;
        $location->measurement_type   = $data->measurement_type;
        $location->alert_limit_total  = $data->alert_limit_total;
        $location->alert_limit_fungi  = $data->alert_limit_fungi;
        $location->alert_action_total = $data->alert_action_total;
        $location->alert_action_fungi = $data->alert_action_fungi;
        $location->save();
    }

    /**
     * Delete a location from the database.
     *
     * @param  Location  $location  The location model to delete.
     * @return void
     */
    public function deleteLocation(Location $location): void
    {
        $location->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getBySection(string $sectionId)
    {
        return Location::query()
            ->where('section_id', $sectionId)
            ->orderBy('section_assigned_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(string $id): Location
    {
        return Location::findOrFail($id);
    }
}
