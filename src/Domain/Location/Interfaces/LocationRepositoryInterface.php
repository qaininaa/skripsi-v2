<?php

namespace Domain\Location\Interfaces;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Models\Location;

interface LocationRepositoryInterface
{
    public function getLocations(GetLocationsFilterDto $data);
    public function getLocationByRoomAndNumber(GetLocationDto $data): ?Location;
    public function createLocation(CreateLocationDto $data): Location;
    public function updateLocation(Location $location, UpdateLocationDto $data): void;
    public function deleteLocation(Location $location): void;
}
