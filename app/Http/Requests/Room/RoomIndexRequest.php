<?php

namespace App\Http\Requests\Room;

use Domain\Room\Dtos\GetRoomsFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class RoomIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'in:A,B,C,D,E'],
        ];
    }

    public function toDTO(): GetRoomsFilterDto
    {
        return new GetRoomsFilterDto($this->validated());
    }
}
