<?php

namespace Domain\Location\Services;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles business logic for the Location domain.
 *
 * Enforces uniqueness rules (room + loc_number combination must be unique)
 * and delegates all data access to the LocationRepositoryInterface.
 */
class LocationService
{
    protected LocationRepositoryInterface $repository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of locations.
     *
     * @param  GetLocationsFilterDto  $dto  Filter parameters (search, room_id).
     * @return LengthAwarePaginator
     */
    public function getDataLocations(GetLocationsFilterDto $dto)
    {
        try {
            return $this->repository->getLocations($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a new location, or return the existing one if the combination already exists.
     *
     * Checks for an existing location with the same room_id and loc_number.
     * If found, returns it without creating a duplicate.
     *
     * @param  CreateLocationDto  $dto  Data for the new location.
     * @return Location The newly created or existing location.
     */
    public function createLocation(CreateLocationDto $dto): Location
    {
        try {
            $existing = $this->repository->getLocationByRoomAndNumber(new GetLocationDto([
                'room_id' => $dto->room_id,
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

    /**
     * Update an existing location.
     *
     * Validates that the new room_id + loc_number combination is not already
     * taken by a different location. Throws RuntimeException if a conflict is found.
     *
     * @param  Location  $location  The location model to update.
     * @param  UpdateLocationDto  $dto  New data for the location.
     *
     * @throws \RuntimeException If another location with the same room and number already exists.
     */
    public function updateLocation(Location $location, UpdateLocationDto $dto): void
    {
        try {
            $existing = $this->repository->getLocationByRoomAndNumber(new GetLocationDto([
                'room_id' => $dto->room_id,
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

    /**
     * Find location by ID.
     */
    public function findLocationById(string $locationId): Location
    {
        return $this->repository->findOrFail($locationId);
    }

    public function updateLocationById(string $locationId, UpdateLocationDto $dto): void
    {
        $this->updateLocation($this->findLocationById($locationId), $dto);
    }

    /**
     * Delete a location.
     *
     * @param  Location  $location  The location model to delete.
     */
    public function deleteLocation(Location $location): void
    {
        try {
            $this->repository->deleteLocation($location);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteLocationById(string $locationId): void
    {
        $this->deleteLocation($this->findLocationById($locationId));
    }
}
