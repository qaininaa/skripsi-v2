<?php

namespace App\Http\Requests\Room;

use Domain\Room\Dtos\CreateRoomDto;
use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'room_number' => ['required', 'string', 'max:255', 'unique:rooms,room_number'],
            'class' => ['required', 'in:A,B,C,D,E'],
        ];
    }

    public function toDTO(): CreateRoomDto
    {
        return new CreateRoomDto($this->validated());
    }
}
