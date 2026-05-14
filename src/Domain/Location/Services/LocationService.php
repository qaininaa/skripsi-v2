<?php

namespace Domain\Location\Services;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
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
            $existing = $this->repository->getLocationByRoomAndNumber(new GetLocationDto([
                'room_id'    => $dto->room_id,
                'loc_number' => $dto->loc_number,
            ]));

            if ($existing !== null) {
                return $existing;
            }

            return $this->repository->createLocation($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateLocation(Location $location, UpdateLocationDto $dto): void
    {
        try {
            $existing = $this->repository->getLocationByRoomAndNumber(new GetLocationDto([
                'room_id'    => $dto->room_id,
                'loc_number' => $dto->loc_number,
                'exclude_id' => $location->id,
            ]));

            if ($existing !== null) {
                throw new \RuntimeException('Lokasi dengan ruangan dan nomor lokasi tersebut sudah ada.');
            }

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
