<?php

namespace Domain\Location\Services;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;

class LocationService
{
    protected LocationRepositoryInterface $repository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataLocations(GetLocationsFilterDto $dto)
    {
        try {
            return $this->repository->getLocations($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createLocation(CreateLocationDto $dto): Location
    {
        try {
            return $this->repository->createLocation($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateLocation(Location $location, UpdateLocationDto $dto): void
    {
        try {
            $this->repository->updateLocation($location, $dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteLocation(Location $location): void
    {
        try {
            $this->repository->deleteLocation($location);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
