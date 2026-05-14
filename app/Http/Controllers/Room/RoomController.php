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
        $room = $this->roomService->createRoom($request->toDTO());

        if (! $room->wasRecentlyCreated) {
            return redirect()
                ->route('rooms.edit', $room)
                ->with('info', 'Nama ruangan sudah ada. Silakan edit data ruangan yang tersedia.');
        }

        return redirect()->route('rooms.index')->with('success', 'Berhasil membuat ruangan baru');
    }

    public function edit(Room $room): View
    {
        return view('room-management.edit', compact('room'));
    }

    public function update(RoomUpdateRequest $request, Room $room): RedirectResponse
    {
        $this->roomService->updateRoom($room, $request->toDTO());

        return redirect()->route('rooms.index')->with('success', 'Berhasil memperbarui ruangan');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->roomService->deleteRoom($room);

        return redirect()->route('rooms.index')->with('success', 'Berhasil menghapus ruangan');
    }

    public function getRooms()
    {
        $rooms = $this->roomService->getDataRooms(new GetRoomsFilterDto());

        return response()->json([
            'success' => true,
            'message' => 'Rooms retrieved successfully',
            'data' => $rooms,
        ]);
    }
}
