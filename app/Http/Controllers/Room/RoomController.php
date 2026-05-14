<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\RoomIndexRequest;
use App\Http\Requests\Room\RoomStoreRequest;
use App\Http\Requests\Room\RoomUpdateRequest;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Models\Room;
use Domain\Room\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomController extends Controller
{
    protected $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(RoomIndexRequest $request): View
    {
        $rooms = $this->roomService->getDataRooms($request->toDTO());

        return view('room-management.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('room-management.create');
    }

    public function store(RoomStoreRequest $request): RedirectResponse
    {
        $this->roomService->createRoom($request->toDTO());

        return redirect()->route('rooms.index')->with('success', 'Room created successfully');
    }

    public function edit(Room $room): View
    {
        return view('room-management.edit', compact('room'));
    }

    public function update(RoomUpdateRequest $request, Room $room): RedirectResponse
    {
        $this->roomService->updateRoom($room, $request->toDTO());

        return redirect()->route('rooms.index')->with('success', 'Room updated successfully');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->roomService->deleteRoom($room);

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully');
    }

    public function getRooms()
    {
        $rooms = $this->roomService->getDataRooms(new GetRoomsFilterDto());

        return response()->json([
            'success' => true,
            'data' => $rooms,
            'message' => 'Rooms retrieved successfully',
        ]);
    }
}
