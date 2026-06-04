<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\LocationIndexRequest;
use App\Http\Requests\Location\LocationStoreRequest;
use App\Http\Requests\Location\LocationUpdateRequest;
use Domain\Location\Services\LocationService;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService,
        protected RoomService $roomService,
    ) {}

    public function index(LocationIndexRequest $request): View
    {
        $locations = $this->locationService->getDataLocations($request->toDTO());
        $rooms = $this->roomService->getDataRooms(new GetRoomsFilterDto);

        return view('location-management.index', compact('locations', 'rooms'));
    }

    public function create(): View
    {
        $rooms = $this->roomService->getDataRooms(new GetRoomsFilterDto);

        return view('location-management.create', compact('rooms'));
    }

    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $location = $this->locationService->createLocation($request->toDTO());

        if (! $location->wasRecentlyCreated) {
            return redirect()
                ->route('location.edit', $location)
                ->with('info', 'Kombinasi ruangan dan nomor lokasi sudah ada. Silakan edit data lokasi yang tersedia.');
        }

        return redirect()->route('location.index')->with('success', 'Berhasil menambahkan lokasi baru');
    }

    public function edit(string $location): View
    {
        $location = $this->locationService->findLocationById($location);
        $rooms = $this->roomService->getDataRooms(new GetRoomsFilterDto);

        return view('location-management.edit', compact('location', 'rooms'));
    }

    public function update(LocationUpdateRequest $request, string $location): RedirectResponse
    {
        try {
            $this->locationService->updateLocationById($location, $request->toDTO());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('location.index')->with('success', 'Berhasil memperbarui lokasi');
    }

    public function destroy(string $location): RedirectResponse
    {
        $this->locationService->deleteLocationById($location);

        return redirect()->route('location.index')->with('success', 'Berhasil menghapus lokasi');
    }
}
