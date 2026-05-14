<?php

namespace App\Http\Requests\Room;

use Domain\Room\Dtos\UpdateRoomDto;
use Illuminate\Foundation\Http\FormRequest;

class RoomUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'room_number' => ['required', 'string', 'max:255'],
            'class' => ['required', 'in:A,B,C,D,E'],
        ];
    }

    public function toDTO(): UpdateRoomDto
    {
        return new UpdateRoomDto($this->validated());
    }
}
