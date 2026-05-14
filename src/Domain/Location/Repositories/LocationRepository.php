<?php

namespace Domain\Location\Repositories;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;

class LocationRepository implements LocationRepositoryInterface
{
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

    public function deleteLocation(Location $location): void
    {
        $location->delete();
    }
}
