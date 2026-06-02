<?php

namespace Domain\Location\Interfaces;

use Domain\Location\Dtos\CreateLocationDto;
use Domain\Location\Dtos\GetLocationDto;
use Domain\Location\Dtos\GetLocationsFilterDto;
use Domain\Location\Dtos\UpdateLocationDto;
use Domain\Location\Models\Location;

/**
 * Contract for Location data access.
 */
interface LocationRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of locations.
     *
     * @param  GetLocationsFilterDto  $data  Filter parameters (search, room_id).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocations(GetLocationsFilterDto $data);

    /**
     * Find a location by room ID and location number (case-insensitive).
     *    
     * @param  GetLocationDto   
     * @return Location|null          
     */
    public function getLocationByRoomAndNumber(GetLocationDto $data): ?Location;

    /**
     * Persist a new location to the database.
     *
     * @param  CreateLocationDto  $data 
     * @return Location                  
     */
    public function createLocation(CreateLocationDto $data): Location;

    /**
     * Update an existing location with new data.
     *
     * @param  Location             
     * @param  UpdateLocationDto       
     * @return void
     */
    public function updateLocation(Location $location, UpdateLocationDto $data): void;

    /**
     * Delete a location from the database.
     *
     * @param  Location   
     * @return void
     */
    public function deleteLocation(Location $location): void;

    /**
     * Find a location by its primary key or throw a ModelNotFoundException.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Location;

    /**
     * Get all locations assigned to a section, ordered by section_assigned_at
     * then created_at. Used when bootstrapping section instance rows.
     *
     * @return \Illuminate\Support\Collection<int, Location>
     */
    public function getBySection(string $sectionId);
}
